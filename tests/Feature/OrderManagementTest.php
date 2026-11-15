<?php

namespace Tests\Feature;

use App\Livewire\Orders\OrderForm;
use App\Models\CustomerOrder;
use App\Models\Partner;
use App\Models\Product;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemoData();
    }

    public function test_order_list_is_visible_to_the_order_admin(): void
    {
        $this->actingAs($this->orderAdmin())
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Pesanan Mitra')
            ->assertSee('CV Maju Motor Sejahtera')
            ->assertSee('Tambah Pesanan');
    }

    public function test_orders_can_be_searched_by_partner_name(): void
    {
        $this->actingAs($this->orderAdmin())
            ->get(route('orders.index', ['cari' => 'Garuda']))
            ->assertOk()
            ->assertSee('PT Garuda Otoparts Mandiri')
            ->assertDontSee('Toko Sparepart Rejeki Jaya');
    }

    public function test_orders_can_be_searched_by_po_number(): void
    {
        $this->actingAs($this->orderAdmin())
            ->get(route('orders.index', ['cari' => 'PO-RJ-0098']))
            ->assertOk()
            ->assertSee('Toko Sparepart Rejeki Jaya');
    }

    public function test_orders_can_be_filtered_by_status(): void
    {
        $this->actingAs($this->orderAdmin())
            ->get(route('orders.index', ['status' => OrderStatus::NEW]))
            ->assertOk()
            ->assertSee('PT Garuda Otoparts Mandiri'); // the only 'baru' order
    }

    public function test_order_detail_shows_fulfilment_figures(): void
    {
        $order = CustomerOrder::where('order_number', 'DEMO-PO-005')->firstOrFail();

        $this->actingAs($this->orderAdmin())
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Sisa kirim')
            ->assertSee('Sudah dikirim');
    }

    // ------------------------------------------------------------ validation

    public function test_all_required_order_fields_are_validated(): void
    {
        $this->actingAs($this->orderAdmin());

        Livewire::test(OrderForm::class)
            ->set('partnerId', null)
            ->set('poNumber', '')
            ->set('productId', null)
            ->set('quantity', '')
            ->call('review')
            ->assertHasErrors(['partnerId', 'poNumber', 'productId', 'quantity']);
    }

    public function test_quantity_must_be_a_positive_integer(): void
    {
        $this->actingAs($this->orderAdmin());

        Livewire::test(OrderForm::class)
            ->set('partnerId', Partner::value('id'))
            ->set('poNumber', 'PO-TEST-1')
            ->set('productId', Product::value('id'))
            ->set('quantity', '0')
            ->call('review')
            ->assertHasErrors('quantity');
    }

    public function test_delivery_target_cannot_be_in_the_past(): void
    {
        $this->actingAs($this->orderAdmin());

        Livewire::test(OrderForm::class)
            ->set('partnerId', Partner::value('id'))
            ->set('poNumber', 'PO-TEST-2')
            ->set('productId', Product::value('id'))
            ->set('quantity', '100')
            ->set('deliveryTargetDate', today()->subWeek()->toDateString())
            ->call('review')
            ->assertHasErrors('deliveryTargetDate');
    }

    // ------------------------------------------------------------ submission

    public function test_review_step_saves_nothing(): void
    {
        $this->actingAs($this->orderAdmin());
        $before = CustomerOrder::count();

        Livewire::test(OrderForm::class)
            ->set('partnerId', Partner::value('id'))
            ->set('poNumber', 'PO-REVIEW-ONLY')
            ->set('productId', Product::value('id'))
            ->set('quantity', '100')
            ->call('review')
            ->assertSet('step', 'confirm');

        $this->assertSame($before, CustomerOrder::count());
    }

    public function test_an_order_is_created_and_becomes_available_to_planning(): void
    {
        $this->actingAs($this->orderAdmin());

        $partner = Partner::where('code', 'MTR-03')->firstOrFail();
        $product = Product::where('sku', 'KNL-RCG-C')->firstOrFail();

        Livewire::test(OrderForm::class)
            ->set('partnerId', $partner->id)
            ->set('poNumber', 'PO-BARU-9001')
            ->set('productId', $product->id)
            ->set('quantity', '450')
            ->set('deliveryTargetDate', today()->addDays(20)->toDateString())
            ->call('review')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('step', 'success');

        $order = CustomerOrder::where('po_number', 'PO-BARU-9001')->firstOrFail();

        $this->assertSame(450, $order->quantity);
        $this->assertSame(OrderStatus::NEW, $order->status);
        $this->assertSame($this->orderAdmin()->id, $order->created_by);
        $this->assertNotNull($order->order_number);

        // Visible to PPIC planning straight away: the new order has no work
        // orders yet, so it appears in "perlu direncanakan".
        $this->actingAs($this->ppic())
            ->get(route('planning.index'))
            ->assertOk()
            ->assertSee($order->order_number);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'customer_order.created',
            'actor_id' => $this->orderAdmin()->id,
        ]);
    }

    public function test_a_duplicate_po_for_the_same_partner_is_rejected(): void
    {
        $this->actingAs($this->orderAdmin());

        $existing = CustomerOrder::where('order_number', 'DEMO-PO-001')->firstOrFail();

        Livewire::test(OrderForm::class)
            ->set('partnerId', $existing->partner_id)
            ->set('poNumber', $existing->po_number)
            ->set('productId', $existing->product_id)
            ->set('quantity', '100')
            ->call('review')
            ->call('save')
            ->assertHasErrors('po_number');

        $this->assertSame(
            1,
            CustomerOrder::where('partner_id', $existing->partner_id)
                ->where('po_number', $existing->po_number)
                ->count(),
        );
    }

    public function test_the_same_po_number_is_allowed_for_a_different_partner(): void
    {
        $this->actingAs($this->orderAdmin());

        $existing = CustomerOrder::where('order_number', 'DEMO-PO-001')->firstOrFail();
        $otherPartner = Partner::whereNot('id', $existing->partner_id)->firstOrFail();

        Livewire::test(OrderForm::class)
            ->set('partnerId', $otherPartner->id)
            ->set('poNumber', $existing->po_number)
            ->set('productId', $existing->product_id)
            ->set('quantity', '100')
            ->call('review')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_a_user_without_create_permission_cannot_open_the_form(): void
    {
        $this->actingAs($this->management())
            ->get(route('orders.create'))
            ->assertForbidden();
    }

    public function test_order_detail_is_refused_to_an_operator(): void
    {
        $order = CustomerOrder::firstOrFail();

        $this->actingAs($this->operator())
            ->get(route('orders.show', $order))
            ->assertForbidden();
    }
}
