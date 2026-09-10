<x-pulse full-width>
    <livewire:pulse.servers cols="full" ignore-after="1 day" />

    <livewire:pulse.usage cols="4" rows="2" />
    <livewire:pulse.usage cols="4" rows="2" type="requests" />
    <livewire:pulse.usage cols="4" rows="2" type="slow_requests" />
    <livewire:pulse.usage cols="4" rows="2" type="jobs" />

    <livewire:pulse.queues cols="4" />
    <livewire:pulse.cache cols="4" />
    <livewire:pulse.exceptions cols="4" />

    <livewire:pulse.slow-requests cols="6" />
    <livewire:pulse.slow-jobs cols="6" />

    <livewire:pulse.slow-queries cols="full" expand />

    <livewire:pulse.slow-outgoing-requests cols="full" />
</x-pulse>
