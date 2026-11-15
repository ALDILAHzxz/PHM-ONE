<?php

namespace Tests\Feature;

use App\Livewire\BeeshaAssistant;
use App\Models\BeeshaMessage;
use App\Models\CustomerOrder;
use App\Models\Permission;
use App\Models\ProductionReport;
use App\Models\ProductionReview;
use App\Models\Shipment;
use App\Models\User;
use App\Services\Beesha\BeeshaAuthorizationException;
use App\Services\Beesha\BeeshaService;
use App\Services\Beesha\BeeshaToolRegistry;
use App\Support\Permissions;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BeeshaAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemoData();
    }

    // ------------------------------------------------------- global presence

    /**
     * Section 11.5: the floating button must be reachable from every
     * authenticated workspace, because it lives in the shared layout.
     */
    public function test_the_floating_button_is_present_on_every_authenticated_workspace(): void
    {
        $pages = [
            'PHM-EMP-001' => ['operator.tasks', 'operator.report.create', 'operator.history', 'help', 'profile'],
            'PHM-EMP-002' => ['supervisor.reviews', 'supervisor.issues', 'supervisor.history'],
            'PHM-EMP-003' => ['orders.index', 'orders.create'],
            'PHM-EMP-004' => ['planning.index', 'planning.work-orders', 'planning.assignments'],
            'PHM-EMP-005' => ['shipments.index', 'shipments.create', 'shipments.history'],
            'PHM-EMP-006' => ['driver.shipments', 'driver.history'],
            'PHM-EMP-007' => ['dashboard', 'dashboard.production', 'dashboard.shipping', 'dashboard.alerts'],
        ];

        foreach ($pages as $employeeNumber => $routes) {
            $user = $this->demoUser($employeeNumber);

            foreach ($routes as $routeName) {
                $this->actingAs($user)
                    ->get(route($routeName))
                    ->assertOk()
                    ->assertSee('Buka Beesha AI', false);
            }
        }
    }

    public function test_the_floating_button_is_not_rendered_for_unauthenticated_visitors(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Buka Beesha AI', false);
    }

    public function test_a_user_without_the_beesha_permission_does_not_get_the_button(): void
    {
        $user = $this->userWithRole(Roles::OPERATOR);
        $user->roles->first()->permissions()->detach(
            Permission::where('code', Permissions::BEESHA_USE)->value('id')
        );

        $this->actingAs($user->fresh(['roles.permissions']))
            ->get(route('operator.tasks'))
            ->assertOk()
            ->assertDontSee('Buka Beesha AI', false);
    }

    // --------------------------------------------------------- conversation

    public function test_chat_opens_and_closes(): void
    {
        $this->actingAs($this->operator());

        Livewire::test(BeeshaAssistant::class)
            ->assertSet('open', false)
            ->call('toggle')
            ->assertSet('open', true)
            ->call('close')
            ->assertSet('open', false);
    }

    public function test_asking_a_question_stores_both_messages(): void
    {
        $this->actingAs($this->operator());

        Livewire::test(BeeshaAssistant::class)
            ->set('question', 'Berapa target produksi saya hari ini?')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('question', '');

        $this->assertSame(1, BeeshaMessage::where('author', 'user')->count());
        $this->assertSame(1, BeeshaMessage::where('author', 'assistant')->count());
    }

    public function test_an_empty_question_is_rejected(): void
    {
        $this->actingAs($this->operator());

        Livewire::test(BeeshaAssistant::class)
            ->set('question', '')
            ->call('send')
            ->assertHasErrors('question');

        $this->assertSame(0, BeeshaMessage::count());
    }

    public function test_suggestions_collapse_after_the_first_question(): void
    {
        $this->actingAs($this->operator());

        Livewire::test(BeeshaAssistant::class)
            ->assertSet('showSuggestions', true)
            ->set('question', 'Berapa target produksi saya hari ini?')
            ->call('send')
            ->assertSet('showSuggestions', false);
    }

    public function test_conversation_history_can_be_cleared(): void
    {
        $this->actingAs($this->operator());

        Livewire::test(BeeshaAssistant::class)
            ->set('question', 'Berapa target produksi saya hari ini?')
            ->call('send')
            ->call('clearConversation');

        $this->assertSame(0, BeeshaMessage::count());
    }

    // ---------------------------------------------------- answer correctness

    public function test_operator_target_question_returns_the_real_figures(): void
    {
        $answer = $this->ask($this->operator(), 'Berapa target produksi saya hari ini?');

        $this->assertStringContainsString('Knalpot Standar Motor A', $answer->content);
        $this->assertStringContainsString('500', $answer->content);
        $this->assertStringContainsString('320', $answer->content);
        $this->assertStringContainsString('180', $answer->content); // remaining
        $this->assertSame('getMyAssignments', $answer->tool_used);
    }

    public function test_answers_distinguish_reported_from_verified(): void
    {
        $answer = $this->ask($this->management(), 'Berapa produksi yang dilaporkan hari ini?');

        $this->assertStringContainsStringIgnoringCase('dilaporkan', $answer->content);
        $this->assertStringContainsStringIgnoringCase('terverifikasi', $answer->content);
        $this->assertSame('getAuthorizedOperationalKPIs', $answer->tool_used);
    }

    public function test_answers_state_the_reporting_period(): void
    {
        $answer = $this->ask($this->management(), 'Berapa produksi yang dilaporkan hari ini?');

        $this->assertStringContainsString(today()->translatedFormat('d F Y'), $answer->content);
    }

    public function test_supervisor_pending_review_question_is_answered_from_data(): void
    {
        $pendingCount = ProductionReport::pending()->count();

        $answer = $this->ask($this->supervisor(), 'Laporan mana yang belum diperiksa?');

        $this->assertSame('getPendingReviews', $answer->tool_used);
        $this->assertStringContainsString((string) $pendingCount, $answer->content);
    }

    public function test_driver_delivery_question_returns_their_own_shipments(): void
    {
        $answer = $this->ask($this->driver(), 'Pengiriman apa yang harus saya selesaikan hari ini?');

        $this->assertSame('getMyShipments', $answer->tool_used);
        $this->assertStringContainsString('DEMO-SJ-003', $answer->content);
    }

    public function test_procedural_questions_are_answered_without_touching_data(): void
    {
        $answer = $this->ask($this->operator(), 'Bagaimana cara mencatat hasil produksi?');

        $this->assertNull($answer->tool_used, 'A how-to answer must not call a data tool.');
        $this->assertStringContainsString('Tugas Saya', $answer->content);
        $this->assertStringContainsString('Catat Hasil Kerja', $answer->content);
    }

    public function test_the_wrong_input_question_explains_the_correction_route(): void
    {
        $answer = $this->ask($this->operator(), 'Apa yang harus saya lakukan kalau salah input?');

        $this->assertStringContainsString('Perlu Perbaikan', $answer->content);
    }

    public function test_an_unrecognised_question_says_so_instead_of_inventing_an_answer(): void
    {
        $answer = $this->ask($this->operator(), 'Berapa harga saham perusahaan tetangga?');

        $this->assertStringContainsString('belum menangkap maksud', $answer->content);
        $this->assertNull($answer->tool_used);
    }

    // ------------------------------------------------------- authorization

    public function test_an_operator_cannot_reach_management_kpis_through_the_assistant(): void
    {
        $this->expectException(BeeshaAuthorizationException::class);

        app(BeeshaToolRegistry::class)->call($this->operator(), 'getAuthorizedOperationalKPIs');
    }

    public function test_an_operator_cannot_reach_the_review_queue_through_the_assistant(): void
    {
        $this->expectException(BeeshaAuthorizationException::class);

        app(BeeshaToolRegistry::class)->call($this->operator(), 'getPendingReviews');
    }

    public function test_an_operator_asking_a_management_question_gets_only_their_own_figures(): void
    {
        $answer = $this->ask($this->operator(), 'Berapa produksi yang dilaporkan hari ini?');

        // Falls back to the operator's own summary, never company-wide KPIs.
        $this->assertNotSame('getAuthorizedOperationalKPIs', $answer->tool_used);
        $this->assertContains($answer->tool_used, ['getMyProductionSummary', 'getMyAssignments', null]);
    }

    public function test_an_operator_does_not_see_another_operators_name_in_an_answer(): void
    {
        $otherName = $this->demoUser('PHM-EMP-010')->full_name;

        foreach ([
            'Berapa target produksi saya hari ini?',
            'Berapa hasil produksi saya hari ini?',
            'Apakah laporan terakhir saya sudah diterima?',
        ] as $question) {
            $answer = $this->ask($this->operator(), $question);

            $this->assertStringNotContainsString(
                $otherName,
                $answer->content,
                'An operator answer leaked another employee name.',
            );
        }
    }

    public function test_tool_catalogue_is_filtered_per_role(): void
    {
        $registry = app(BeeshaToolRegistry::class);

        $operatorTools = $registry->availableTo($this->operator());
        $this->assertContains('getMyAssignments', $operatorTools);
        $this->assertNotContains('getPendingReviews', $operatorTools);
        $this->assertNotContains('getAuthorizedOperationalKPIs', $operatorTools);

        $managementTools = $registry->availableTo($this->management());
        $this->assertContains('getAuthorizedOperationalKPIs', $managementTools);
        $this->assertContains('getOperationalAlerts', $managementTools);
        $this->assertNotContains('getMyAssignments', $managementTools);
    }

    public function test_no_tool_in_the_catalogue_performs_a_write(): void
    {
        $registry = app(BeeshaToolRegistry::class);

        foreach (array_keys($registry->catalogue()) as $tool) {
            $before = [
                ProductionReport::count(),
                ProductionReview::count(),
                Shipment::count(),
                CustomerOrder::count(),
            ];

            foreach ([$this->operator(), $this->supervisor(), $this->management(), $this->driver(), $this->warehouse()] as $user) {
                if ($registry->userCanCall($user, $tool)) {
                    $registry->call($user, $tool);
                }
            }

            $after = [
                ProductionReport::count(),
                ProductionReview::count(),
                Shipment::count(),
                CustomerOrder::count(),
            ];

            $this->assertSame($before, $after, sprintf('Tool %s changed data.', $tool));
        }
    }

    public function test_suggestions_are_filtered_to_what_the_user_may_ask(): void
    {
        $service = app(BeeshaService::class);

        $operatorSuggestions = implode(' ', $service->suggestionsFor($this->operator()));
        $this->assertStringNotContainsString('belum diperiksa', $operatorSuggestions);

        $supervisorSuggestions = implode(' ', $service->suggestionsFor($this->supervisor()));
        $this->assertStringContainsString('belum diperiksa', $supervisorSuggestions);
    }

    /** Every suggested question must actually resolve to a permitted answer. */
    public function test_every_suggested_question_produces_a_usable_answer(): void
    {
        $service = app(BeeshaService::class);

        foreach (['PHM-EMP-001', 'PHM-EMP-002', 'PHM-EMP-003', 'PHM-EMP-005', 'PHM-EMP-006', 'PHM-EMP-007'] as $employeeNumber) {
            $user = $this->demoUser($employeeNumber);

            foreach ($service->suggestionsFor($user) as $question) {
                $answer = $this->ask($user, $question);

                $this->assertStringNotContainsString(
                    'belum menangkap maksud',
                    $answer->content,
                    sprintf('Suggested question "%s" was not understood for %s.', $question, $employeeNumber),
                );
                $this->assertStringNotContainsString('di luar izin akses', $answer->content);
            }
        }
    }

    public function test_the_demo_provider_is_used_and_labelled_when_no_api_key_exists(): void
    {
        config(['phm.beesha.provider' => 'demo', 'phm.beesha.api_key' => null]);

        $this->assertTrue(app(BeeshaService::class)->isDemoProvider());

        $this->actingAs($this->operator())
            ->get(route('operator.tasks'))
            ->assertOk()
            ->assertSee('Asisten demo', false);
    }

    public function test_tool_access_is_audited(): void
    {
        $this->ask($this->operator(), 'Berapa target produksi saya hari ini?');

        $this->assertDatabaseHas('audit_events', [
            'action' => 'beesha.tool_called',
            'actor_id' => $this->operator()->id,
        ]);
    }

    private function ask(User $user, string $question): BeeshaMessage
    {
        return app(BeeshaService::class)->ask($user, 'test-conv-'.$user->id, $question);
    }
}
