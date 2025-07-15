<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        My Support Tickets
    </x-slot>
    
    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
    </x-slot>

    <div class="container py-4">
        <div class="d-flex justify-content-between mb-3">
            <h4>Support Tickets</h4>
            <a href="{{ route('user.tickets.create') }}" class="btn btn-primary">+ New Ticket</a>
        </div>

        <div class="mb-3">
            <form method="GET" class="d-flex gap-2">
                <select name="status" class="form-select w-auto">
                    <option value="">All Status</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
                <button type="submit" class="btn btn-outline-secondary">Filter</button>
            </form>
        </div>

        @forelse($tickets as $ticket)
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">{{ $ticket->subject }}</h5>
                    <p class="card-text text-muted">
                        Status:
                        <span class="badge {{ $ticket->status === 'closed' ? 'bg-danger' : ($ticket->status === 'pending' ? 'bg-warning text-dark' : 'bg-success') }}">
                            {{ ucfirst($ticket->status) }}
                        </span>
                        <span class="ms-3">Created: {{ $ticket->created_at->format('d M Y, H:i') }}</span>
                    </p>
                    <a href="{{ route('user.tickets.show', $ticket->id) }}" class="btn btn-sm btn-outline-info">View</a>
                </div>
            </div>
        @empty
            <div class="alert alert-info">No tickets found.</div>
        @endforelse

        <div class="mt-4">
            {{ $tickets->links('vendor.pagination.bootstrap-5') }}
        </div>
        
    </div>
    <x-slot:footerFiles></x-slot>
</x-base-layout>
