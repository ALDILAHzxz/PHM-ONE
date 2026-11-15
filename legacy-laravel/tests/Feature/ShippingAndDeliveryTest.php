<?php

namespace Tests\Feature;

use App\Livewire\Driver\DeliveryList;
use App\Livewire\Shipping\ShipmentForm;
use App\Models\CustomerOrder;
use App\Models\Shipment;
use App\Services\OperationsMetrics;
use App\Services\ShipmentService;
use App\Support\OrderStatus;
use App\Support\ShipmentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ShippingAndDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemoData();
    }

    // -------------------------------------------------------------- shipping

    public function test_warehouse_sees_the_shipping_workspace(): void
    {
        $this->actingAs($this->warehouse())
            ->get(route('shipments.index'))
            ->assertOk()
            ->assertSee('Pengiriman Hari Ini')
            ->assertSee('Barang Siap Dikirim');
    }

    public function test_shipment_quantity_is_validated(): void
    {
        $this->actingAs($this->warehouse());

        Livewire::test(ShipmentForm::class)
            ->set('quantity', '0')
            ->call('review')
            ->assertHasErrors('quantity');
    }

    public function test_a_partial_shipment_can_be_recorded(): void
    {
        $order = $this->orderWithVerifiedStock();
        $ready = $order->readyToShipQuantity();
        $this->assertGreaterThan(1, $ready, 'The fixture needs verified stock to ship.');

        $this->actingAs($this->warehouse());

        Livewire::test(ShipmentForm::class)
            ->set('orderId', $order->id)
            ->set('quantity', (string) (int) floor($ready / 2))
            ->call('review')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('step', 'success');

        $shipment = Shipment::latest('id')->first();

        $this->assertSame($order->id, $shipment->customer_order_id);
        $this->assertSame(ShipmentStatus::PREPARED, $shipment->status);
        $this->assertNotNull($shipment->delivery_note_number, 'A delivery note number is generated.');
        $this->assertNotNull($shipment->shipment_number);
    }

    public function test_shipping_more_than_the_remaining_order_quantity_is_refused(): void
    {
        $order = $this->orderWithVerifiedStock();

        $this->expectException(ValidationException::class);

        app(ShipmentService::class)->record($this->warehouse(), [
            'customer_order_id' => $order->id,
            'quantity' => $order->quantity + 1_000,
            'delivery_note_number' => null,
            'driver_id' => null,
            'notes' => null,
            'idempotency_key' => 'over-order',
        ]);
    }

    /** Only verified production counts as goods that exist. */
    public function test_shipping_more_than_the_verified_stock_is_refused(): void
    {
        $order = CustomerOrder::where('order_number', 'DEMO-PO-004')->firstOrFail();

        $this->assertSame(0, $order->verifiedProducedQuantity(), 'This order has no verified production.');

        $this->expectException(ValidationException::class);

        app(ShipmentService::class)->record($this->warehouse(), [
            'customer_order_id' => $order->id,
            'quantity' => 10,
            'delivery_note_number' => null,
            'driver_id' => null,
            'notes' => null,
            'idempotency_key' => 'no-verified-stock',
        ]);
    }

    public function test_a_duplicate_shipment_submission_creates_one_record(): void
    {
        $order = $this->orderWithVerifiedStock();
        $service = app(ShipmentService::class);

        $payload = [
            'customer_order_id' => $order->id,
            'quantity' => 1,
            'delivery_note_number' => null,
            'driver_id' => null,
            'notes' => null,
            'idempotency_key' => 'shipment-double-click',
        ];

        $first = $service->record($this->warehouse(), $payload);
        $second = $service->record($this->warehouse(), $payload);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Shipment::where('idempotency_key', 'shipment-double-click')->count());
    }

    public function test_recording_a_shipment_updates_the_order_fulfilment_status(): void
    {
        $order = $this->orderWithVerifiedStock();

        app(ShipmentService::class)->record($this->warehouse(), [
            'customer_order_id' => $order->id,
            'quantity' => 1,
            'delivery_note_number' => null,
            'driver_id' => $this->driver()->id,
            'notes' => null,
            'idempotency_key' => 'fulfilment-refresh',
        ]);

        $this->assertContains(
            $order->fresh()->status,
            [OrderStatus::IN_PRODUCTION, OrderStatus::PARTIALLY_SHIPPED, OrderStatus::READY, OrderStatus::COMPLETED],
        );
    }

    public function test_an_operator_cannot_record_a_shipment(): void
    {
        $this->actingAs($this->operator())
            ->get(route('shipments.create'))
            ->assertForbidden();
    }

    // --------------------------------------------------------------- driver

    public function test_driver_sees_only_their_own_deliveries(): void
    {
        $otherDriverShipment = Shipment::where('driver_id', $this->demoUser('PHM-EMP-013')->id)->first();

        $visible = Livewire::actingAs($this->driver())
            ->test(DeliveryList::class)
            ->instance()
            ->shipments();

        $this->assertNotEmpty($visible);

        foreach ($visible as $shipment) {
            $this->assertSame($this->driver()->id, $shipment->driver_id);
        }

        if ($otherDriverShipment !== null) {
            $this->assertFalse($visible->contains('id', $otherDriverShipment->id));
        }
    }

    public function test_driver_can_start_a_prepared_delivery(): void
    {
        $shipment = Shipment::where('driver_id', $this->driver()->id)
            ->where('status', ShipmentStatus::PREPARED)
            ->firstOrFail();

        Livewire::actingAs($this->driver())
            ->test(DeliveryList::class)
            ->call('startAction', $shipment->id, ShipmentStatus::IN_TRANSIT);

        $fresh = $shipment->fresh();
        $this->assertSame(ShipmentStatus::IN_TRANSIT, $fresh->status);
        $this->assertNotNull($fresh->shipped_at);

        $this->assertDatabaseHas('delivery_status_events', [
            'shipment_id' => $shipment->id,
            'status' => ShipmentStatus::IN_TRANSIT,
            'actor_id' => $this->driver()->id,
        ]);
    }

    public function test_marking_goods_received_requires_a_recipient_name(): void
    {
        $shipment = Shipment::where('driver_id', $this->driver()->id)
            ->where('status', ShipmentStatus::IN_TRANSIT)
            ->firstOrFail();

        // in_transit -> arrived -> received
        app(ShipmentService::class)->updateStatus($this->driver(), $shipment, ShipmentStatus::ARRIVED);

        Livewire::actingAs($this->driver())
            ->test(DeliveryList::class)
            ->call('startAction', $shipment->id, ShipmentStatus::RECEIVED)
            ->set('recipientName', '')
            ->call('confirmAction')
            ->assertHasErrors('recipientName');

        $this->assertSame(ShipmentStatus::ARRIVED, $shipment->fresh()->status);
    }

    public function test_a_full_delivery_journey_is_recorded(): void
    {
        $shipment = Shipment::where('driver_id', $this->driver()->id)
            ->where('status', ShipmentStatus::PREPARED)
            ->firstOrFail();

        $component = Livewire::actingAs($this->driver())->test(DeliveryList::class);

        $component->call('startAction', $shipment->id, ShipmentStatus::IN_TRANSIT);
        $component->call('startAction', $shipment->id, ShipmentStatus::ARRIVED);
        $component->call('startAction', $shipment->id, ShipmentStatus::RECEIVED)
            ->set('recipientName', 'Bapak Hendra')
            ->call('confirmAction')
            ->assertHasNoErrors();

        $fresh = $shipment->fresh();
        $this->assertSame(ShipmentStatus::RECEIVED, $fresh->status);
        $this->assertNotNull($fresh->delivered_at);

        $this->assertDatabaseHas('delivery_status_events', [
            'shipment_id' => $shipment->id,
            'status' => ShipmentStatus::RECEIVED,
            'recipient_name' => 'Bapak Hendra',
        ]);

        // Four events: prepared (from recording) + the three transitions.
        $this->assertGreaterThanOrEqual(4, $fresh->events()->count());
    }

    public function test_reporting_a_delivery_problem_requires_an_explanation(): void
    {
        $shipment = Shipment::where('driver_id', $this->driver()->id)
            ->where('status', ShipmentStatus::IN_TRANSIT)
            ->firstOrFail();

        Livewire::actingAs($this->driver())
            ->test(DeliveryList::class)
            ->call('startAction', $shipment->id, ShipmentStatus::ISSUE)
            ->set('actionNotes', '')
            ->call('confirmAction')
            ->assertHasErrors('actionNotes');

        $this->assertSame(ShipmentStatus::IN_TRANSIT, $shipment->fresh()->status);
    }

    public function test_a_reported_problem_is_stored_and_surfaces_as_an_alert(): void
    {
        $shipment = Shipment::where('driver_id', $this->driver()->id)
            ->where('status', ShipmentStatus::IN_TRANSIT)
            ->firstOrFail();

        Livewire::actingAs($this->driver())
            ->test(DeliveryList::class)
            ->call('startAction', $shipment->id, ShipmentStatus::ISSUE)
            ->set('actionNotes', 'Kendaraan mogok di Tol Cikampek, estimasi terlambat 3 jam.')
            ->call('confirmAction')
            ->assertHasNoErrors();

        $this->assertSame(ShipmentStatus::ISSUE, $shipment->fresh()->status);

        $alerts = app(OperationsMetrics::class)->alerts();
        $titles = $alerts->pluck('title')->implode(' | ');

        $this->assertStringContainsString($shipment->shipment_number, $titles);
    }

    // ------------------------------------------------------ invalid transitions

    public function test_an_invalid_status_transition_is_refused(): void
    {
        $shipment = Shipment::where('driver_id', $this->driver()->id)
            ->where('status', ShipmentStatus::PREPARED)
            ->firstOrFail();

        // prepared -> received is not a legal jump.
        $this->expectException(ValidationException::class);

        app(ShipmentService::class)->updateStatus(
            $this->driver(),
            $shipment,
            ShipmentStatus::RECEIVED,
            null,
            'Seseorang',
        );
    }

    public function test_a_received_shipment_cannot_move_again(): void
    {
        $shipment = Shipment::where('status', ShipmentStatus::RECEIVED)->firstOrFail();

        $this->assertSame([], $shipment->allowedNextStatuses());

        $this->expectException(ValidationException::class);

        app(ShipmentService::class)->updateStatus(
            $this->warehouse(),
            $shipment,
            ShipmentStatus::IN_TRANSIT,
        );
    }

    public function test_shipping_history_lists_completed_deliveries(): void
    {
        $this->actingAs($this->warehouse())
            ->get(route('shipments.history'))
            ->assertOk()
            ->assertSee('Riwayat Pengiriman')
            ->assertSee('DEMO-SJ-001');
    }

    public function test_driver_history_is_scoped_to_the_signed_in_driver(): void
    {
        $this->actingAs($this->driver())
            ->get(route('driver.history'))
            ->assertOk()
            ->assertSee('DEMO-SJ-001')       // delivered by PHM-EMP-006
            ->assertDontSee('DEMO-SJ-002');  // delivered by PHM-EMP-013
    }

    private function orderWithVerifiedStock(): CustomerOrder
    {
        return CustomerOrder::active()
            ->with(['partner', 'product'])
            ->get()
            ->sortByDesc(fn (CustomerOrder $o) => $o->readyToShipQuantity())
            ->first();
    }
}
