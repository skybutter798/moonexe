<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        Support Tickets
    </x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-xl-3 col-lg-4 col-md-5">
            <div class="mail-sidebar-scroll">
                <ul class="nav nav-pills d-block" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') == null ? 'active' : '' }}"
                           href="{{ route('admin.tickets.index') }}">
                           All Tickets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') == 'open' ? 'active' : '' }}"
                           href="{{ route('admin.tickets.index', ['status' => 'open']) }}">
                           Open
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') == 'pending' ? 'active' : '' }}"
                           href="{{ route('admin.tickets.index', ['status' => 'pending']) }}">
                           Pending
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') == 'closed' ? 'active' : '' }}"
                           href="{{ route('admin.tickets.index', ['status' => 'closed']) }}">
                           Closed
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-xl-9 col-lg-8 col-md-7">
            <div class="widget p-4">
                <div class="widget-header d-flex align-items-center">
                    <h4 class="mb-0">Support Tickets</h4>
                    <a href="{{ route('admin.tickets.create') }}" class="btn btn-sm btn-primary">+ New Ticket</a>
                </div>

                <form method="GET" class="row gy-2 gx-2 mt-3">
                    <div class="col">
                        <input type="text" name="username" class="form-control form-control-sm"
                               placeholder="Search by username..." value="{{ request('username') }}">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-outline-dark" type="submit">Filter</button>
                        <a href="{{ route('admin.tickets.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </form>

                <hr>

                @forelse($tickets as $ticket)
                    <div class="mail-item {{ $ticket->status }} mb-3 p-3 border rounded">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="mb-1">{{ $ticket->subject }}</h6>
                                <small class="text-muted">User: {{ $ticket->user->name }}</small><br>
                                <small class="text-muted">Assigned To: {{ $ticket->assignedTo->name ?? '—' }}</small><br>
                                <span class="badge 
                                    {{ $ticket->status == 'closed' ? 'bg-danger' : 
                                       ($ticket->status == 'pending' ? 'bg-primary' : 'bg-primary') }}">
                                    {{ ucfirst($ticket->status) }}
                                </span>
                                <span class="badge 
                                    {{ $ticket->priority == 'high' ? 'bg-danger' : 
                                       ($ticket->priority == 'medium' ? 'bg-primary' : 'bg-secondary') }}">
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                            </div>
                            <div class="text-end">
                                <p class="mb-1"><small>{{ $ticket->created_at->format('d M Y, H:i') }}</small></p>
                                <a href="{{ route('admin.tickets.show', $ticket->id) }}"
                                   class="btn btn-sm btn-outline-info">View</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center p-4">
                        <p class="mb-0">No tickets found.</p>
                    </div>
                @endforelse

                <div class="mt-4">
                    {{ $tickets->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

    <x-slot:footerFiles></x-slot>
</x-base-layout>
