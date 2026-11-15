<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The non-negotiable interface requirements from master specification
 * sections 07, 09, 11, 14 and 44, asserted against rendered markup.
 */
class InterfaceContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemoData();
    }

    private function renderOperatorHome(): string
    {
        return $this->actingAs($this->operator())
            ->get(route('operator.tasks'))
            ->assertOk()
            ->getContent();
    }

    private function navbarMarkup(string $html): string
    {
        $this->assertSame(
            1,
            preg_match('#<header\b[^>]*>(.*?)</header>#s', $html, $matches),
            'The page must render exactly one navbar header.',
        );

        return $matches[1];
    }

    /** Text a human actually sees, with all tags and attributes removed. */
    private function visibleText(string $markup): string
    {
        $text = preg_replace('#<script\b[^>]*>.*?</script>#s', ' ', $markup);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    // ------------------------------------------------------------- 07 navbar

    public function test_navbar_is_sticky_at_the_top(): void
    {
        $navbar = $this->navbarMarkup($this->renderOperatorHome());
        $html = $this->renderOperatorHome();

        $this->assertMatchesRegularExpression(
            '#<header[^>]*class="[^"]*\bsticky\b[^"]*"#',
            $html,
            'The navbar must use position: sticky.',
        );
        $this->assertMatchesRegularExpression(
            '#<header[^>]*class="[^"]*\btop-0\b[^"]*"#',
            $html,
            'The navbar must stick to top: 0.',
        );
        $this->assertMatchesRegularExpression(
            '#<header[^>]*class="[^"]*\bz-50\b[^"]*"#',
            $html,
            'The navbar needs a z-index above page content.',
        );
        $this->assertNotEmpty($navbar);
    }

    public function test_navbar_height_follows_the_specification(): void
    {
        $html = $this->renderOperatorHome();

        // 64px mobile (h-16) escalating to 72px desktop (md:h-18).
        $this->assertStringContainsString('h-16', $html);
        $this->assertStringContainsString('md:h-18', $html);
    }

    /**
     * Section 7.2: navbar action controls are ICON ONLY. The accessible names
     * live in attributes; no visible text may sit beside those icons.
     */
    public function test_navbar_action_controls_show_no_visible_text_labels(): void
    {
        $navbar = $this->navbarMarkup($this->renderOperatorHome());
        $visible = $this->visibleText($navbar);

        // The brand name and page title are allowed as ordinary navbar text.
        $allowed = ['PHM One', 'Tugas Saya', 'P1'];

        foreach ($allowed as $permitted) {
            $visible = str_replace($permitted, '', $visible);
        }

        $visible = trim(preg_replace('/\s+/', ' ', $visible));

        $this->assertSame(
            '',
            $visible,
            'The navbar must not render any visible text besides the brand and page title. Found: "'.$visible.'"',
        );

        // Specifically, none of these forbidden labels may be visible.
        foreach (['Menu', 'Dark Mode', 'Light Mode', 'Mode Gelap', 'Mode Terang', 'Profil', 'Pengaturan'] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $this->visibleText($navbar),
                sprintf('"%s" must not appear as visible navbar text.', $forbidden),
            );
        }
    }

    /** Icon-only buttons still need accessible names (section 7.2). */
    public function test_navbar_icon_buttons_have_accessible_names(): void
    {
        $navbar = $this->navbarMarkup($this->renderOperatorHome());

        $this->assertStringContainsString('aria-label="Buka menu navigasi"', $navbar);
        $this->assertStringContainsString('aria-label="Profil dan pengaturan akun"', $navbar);
        // The theme toggle's name is bound reactively because it describes the action.
        $this->assertStringContainsString('Ubah ke mode gelap', $navbar);
        $this->assertStringContainsString('Ubah ke mode terang', $navbar);
    }

    /** Section 44: theme icon then profile icon, both at the top-right. */
    public function test_theme_and_profile_controls_are_the_last_two_navbar_controls(): void
    {
        $navbar = $this->navbarMarkup($this->renderOperatorHome());

        $hamburger = strpos($navbar, 'Buka menu navigasi');
        $theme = strpos($navbar, 'Ubah ke mode');
        $profile = strpos($navbar, 'Profil dan pengaturan akun');

        $this->assertNotFalse($hamburger);
        $this->assertNotFalse($theme);
        $this->assertNotFalse($profile);

        $this->assertLessThan($theme, $hamburger, 'The hamburger belongs on the left.');
        $this->assertLessThan($profile, $theme, 'The theme toggle comes immediately before the profile icon.');
    }

    public function test_navbar_icon_buttons_meet_the_minimum_touch_target(): void
    {
        $html = $this->renderOperatorHome();

        // .phm-icon-btn is size-11 (44px) growing to size-12 (48px) on desktop.
        $this->assertStringContainsString('phm-icon-btn', $html);

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertMatchesRegularExpression(
            '/@utility phm-icon-btn \{.*?size-11.*?\}/s',
            $css,
            'Navbar icon buttons must be at least 44px.',
        );
    }

    // ------------------------------------------------------------- 08 drawer

    public function test_drawer_entries_keep_readable_text_labels(): void
    {
        $html = $this->renderOperatorHome();

        $this->assertSame(1, preg_match('#<aside\b[^>]*>(.*?)</aside>#s', $html, $m));
        $drawer = $m[1];

        // The icon-only rule applies to navbar controls, not drawer rows.
        foreach (['Tugas Saya', 'Catat Hasil', 'Riwayat Saya', 'Bantuan', 'Keluar'] as $label) {
            $this->assertStringContainsString($label, $this->visibleText($drawer));
        }
    }

    public function test_drawer_can_be_closed_by_escape_and_backdrop(): void
    {
        $html = $this->renderOperatorHome();

        $this->assertStringContainsString('@keydown.escape.window="drawer = false"', $html);
        $this->assertStringContainsString('aria-label="Tutup menu navigasi"', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
    }

    public function test_drawer_rows_meet_the_minimum_height(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/@utility phm-drawer-link \{.*?min-h-\[3\.25rem\].*?\}/s',
            $css,
            'Drawer rows must be at least 52px tall.',
        );
    }

    // -------------------------------------------------------------- 09 theme

    public function test_theme_is_applied_before_first_paint_to_avoid_a_flash(): void
    {
        $html = $this->renderOperatorHome();

        $this->assertStringContainsString("localStorage.getItem('phm-theme')", $html);
        $this->assertStringContainsString("classList.toggle('dark'", $html);

        // The script must run in <head>, before the body renders.
        $headEnd = strpos($html, '</head>');
        $script = strpos($html, "localStorage.getItem('phm-theme')");
        $this->assertLessThan($headEnd, $script, 'The theme script must be in <head>.');
    }

    public function test_theme_choice_is_persisted(): void
    {
        $html = $this->renderOperatorHome();

        $this->assertStringContainsString("localStorage.setItem('phm-theme'", $html);
    }

    public function test_both_themes_define_every_semantic_colour(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $tokens = [
            '--phm-page', '--phm-surface', '--phm-body', '--phm-muted',
            '--phm-interactive', '--phm-border', '--phm-navbar',
        ];

        foreach ($tokens as $token) {
            // Defined for light (:root) and redefined for dark (:root.dark).
            $this->assertMatchesRegularExpression(
                '/:root \{[^}]*'.preg_quote($token, '/').':/s',
                $css,
                $token.' must be defined for light mode.',
            );
            $this->assertMatchesRegularExpression(
                '/:root\.dark \{[^}]*'.preg_quote($token, '/').':/s',
                $css,
                $token.' must be redefined for dark mode.',
            );
        }
    }

    public function test_the_corporate_palette_values_are_used(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        foreach (['#1b365d', '#0077c8', '#f2f4f7', '#1e293b', '#0b111b', '#172131', '#f2f7ff', '#bdcce0', '#83c7ff', '#8ccbff'] as $hex) {
            $this->assertStringContainsString($hex, strtolower($css), $hex.' is part of the approved palette.');
        }
    }

    // ---------------------------------------------------------- 11 Beesha FAB

    public function test_the_floating_button_is_fixed_to_the_bottom_right(): void
    {
        $html = $this->renderOperatorHome();

        $this->assertSame(
            1,
            preg_match('#<div[^>]*class="([^"]*)"[^>]*>\s*<button[^>]*aria-label="Buka Beesha AI#s', $html, $m),
            'The floating button must sit in a positioned wrapper.',
        );

        $classes = $m[1];
        $this->assertStringContainsString('fixed', $classes);
        $this->assertStringContainsString('right-', $classes);
        $this->assertStringContainsString('bottom-', $classes);
        $this->assertStringContainsString('z-40', $classes);
    }

    public function test_the_floating_button_sits_below_the_drawer_overlay(): void
    {
        $html = $this->renderOperatorHome();

        // Drawer overlay is z-60; the FAB is z-40 and the chat panel z-50.
        $this->assertStringContainsString('z-[60]', $html);
        $this->assertStringContainsString('z-40', $html);
    }

    public function test_page_content_leaves_room_so_the_fab_covers_nothing(): void
    {
        $html = $this->renderOperatorHome();

        $this->assertMatchesRegularExpression(
            '#<main[^>]*class="[^"]*\bpb-28\b[^"]*"#',
            $html,
            'The main region must pad its bottom so page actions clear the floating button.',
        );
    }

    public function test_the_chat_panel_has_a_close_button_and_an_input(): void
    {
        $html = $this->renderOperatorHome();

        $this->assertStringContainsString('aria-label="Tutup Beesha AI"', $html);
        $this->assertStringContainsString('Pertanyaan untuk Beesha', $html);
        $this->assertStringContainsString('aria-label="Kirim pertanyaan"', $html);
    }

    // ------------------------------------------------- 14 production form UX

    public function test_the_production_form_is_a_single_centred_column(): void
    {
        $html = $this->actingAs($this->operator())
            ->get(route('operator.report.create'))
            ->assertOk()
            ->getContent();

        // max-w-[45rem] is 720px, the upper bound from section 14.
        $this->assertStringContainsString('mx-auto w-full max-w-[45rem]', $html);
    }

    public function test_the_production_form_shows_known_values_as_read_only_context(): void
    {
        $html = $this->actingAs($this->operator())
            ->get(route('operator.report.create'))
            ->assertOk()
            ->getContent();

        foreach (['Informasi Pekerjaan', 'Nama produk', 'Nomor pekerjaan', 'Nama petugas', 'Target', 'Sudah tercatat', 'Sisa target'] as $label) {
            $this->assertStringContainsString($label, $html);
        }

        // There must be exactly one number input: the quantity.
        $this->assertSame(
            1,
            preg_match_all('/<input[^>]*type="number"/', $html),
            'The operator should only ever type one number.',
        );
    }

    public function test_the_production_form_hides_issue_fields_until_an_issue_is_reported(): void
    {
        $html = $this->actingAs($this->operator())
            ->get(route('operator.report.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Apakah ada kendala?', $html);
        $this->assertStringNotContainsString('Jelaskan Kendala', $html);
        $this->assertStringNotContainsString('Jenis Kendala', $html);
    }

    public function test_the_production_form_has_one_dominant_submit_action(): void
    {
        $html = $this->actingAs($this->operator())
            ->get(route('operator.report.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Periksa &amp; Simpan Hasil', $html);

        // Exactly one primary button; cancel stays subordinate (ghost).
        $this->assertSame(
            1,
            preg_match_all('/phm-btn phm-btn-primary/', $html),
            'The form must present a single visually dominant action.',
        );
        $this->assertStringContainsString('phm-btn phm-btn-ghost', $html);
    }

    // ---------------------------------------------------- 30/33 mobile & a11y

    public function test_the_viewport_supports_mobile_and_safe_areas(): void
    {
        $html = $this->renderOperatorHome();

        $this->assertStringContainsString(
            'content="width=device-width, initial-scale=1, viewport-fit=cover"',
            $html,
        );
    }

    public function test_a_skip_link_is_the_first_focusable_element(): void
    {
        $html = $this->renderOperatorHome();

        $this->assertStringContainsString('Lewati ke konten utama', $html);
        $this->assertStringContainsString('id="konten-utama"', $html);

        $skip = strpos($html, 'Lewati ke konten utama');
        $navbar = strpos($html, 'Buka menu navigasi');
        $this->assertLessThan($navbar, $skip, 'The skip link must precede the navbar.');
    }

    public function test_reduced_motion_is_respected(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);
    }

    public function test_focus_is_always_visible(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression('/:focus-visible \{[^}]*outline:/s', $css);
    }

    public function test_inputs_and_buttons_meet_the_specified_sizes(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // input 56px, primary button >= 52px
        $this->assertMatchesRegularExpression('/@utility phm-input \{.*?min-h-\[3\.5rem\].*?\}/s', $css);
        $this->assertMatchesRegularExpression('/@utility phm-btn \{.*?min-h-\[3\.25rem\].*?\}/s', $css);
    }

    public function test_only_clickable_cards_get_the_hover_lift(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // The lift lives on a separate utility applied only to links/buttons.
        $this->assertStringContainsString('@utility phm-card-interactive', $css);

        $html = $this->actingAs($this->operator())
            ->get(route('operator.tasks'))
            ->getContent();

        // Tugas Saya has no clickable cards, so no lift class should appear.
        $this->assertStringNotContainsString('phm-card-interactive', $html);
    }

    public function test_demo_mode_is_disclosed_on_every_authenticated_page(): void
    {
        foreach (['operator.tasks', 'operator.history', 'help', 'profile'] as $routeName) {
            $this->actingAs($this->operator())
                ->get(route($routeName))
                ->assertOk()
                ->assertSee('Mode Demo', false);
        }
    }
}
