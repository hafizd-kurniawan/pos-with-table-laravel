<x-filament-panels::page>
    <x-filament-panels::form wire:submit="submit">
        {{ $this->form }}
        
        <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
        />
    </x-filament-panels::form>
    
    @push('scripts')
    <script>
        // Auto-focus on first input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('input[type="password"]');
            if (firstInput) {
                firstInput.focus();
            }
        });
    </script>
    @endpush
</x-filament-panels::page>
