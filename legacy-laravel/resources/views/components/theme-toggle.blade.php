{{--
    Icon-only Light/Dark switch (master specification section 09).

    - Sun icon is shown while the dark theme is active, because the action it
      performs is "switch to light".
    - The accessible name describes the ACTION, and is never rendered as visible
      text next to the icon.
    - The choice is persisted in localStorage and applied before first paint by
      the inline script in the layout head, so the theme never flashes.
--}}
<button type="button"
        class="phm-icon-btn"
        x-data="{
            dark: document.documentElement.classList.contains('dark'),
            toggle() {
                this.dark = !this.dark;
                document.documentElement.classList.toggle('dark', this.dark);
                try {
                    localStorage.setItem('phm-theme', this.dark ? 'dark' : 'light');
                } catch (e) {
                    /* storage blocked — the theme still applies for this page */
                }
            },
        }"
        @click="toggle()"
        :aria-label="dark ? 'Ubah ke mode terang' : 'Ubah ke mode gelap'"
        :title="dark ? 'Mode terang' : 'Mode gelap'">
    <x-lucide-sun class="hidden size-6 dark:block" aria-hidden="true" />
    <x-lucide-moon class="size-6 dark:hidden" aria-hidden="true" />
</button>
