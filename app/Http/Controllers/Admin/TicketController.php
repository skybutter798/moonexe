<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\TicketAttachment;
use App\Models\User;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with('user', 'assignedTo');
    
        // Apply status filter if provided
        if ($request->has('status') && in_array($request->status, ['open', 'pending', 'closed'])) {
            $query->where('status', $request->status);
        }
    
        // Optional: Search by username
        if ($request->filled('username')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->username . '%');
            });
        }
    
        $tickets = $query->latest()->paginate(20);
    
        return view('admin.tickets.index', compact('tickets'));
    }

    public function create()
    {
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();
        return view('admin.tickets.create', compact('users'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attachments.*' => 'file|max:10240',
        ]);
    
        $ticket = Ticket::create([
            'user_id' => $request->user_id,
            'assigned_to' => auth()->id(),
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'open',
            'priority' => 'medium',
        ]);
    
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('tickets', 'public');
    
                TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'filename' => $file->getClientOriginalName(),
                    'filepath' => $path,
                    'mime_type' => $file->getMimeType(),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }
    
        return redirect()->route('admin.tickets.show', $ticket->id)->with('success', 'Ticket created.');
    }


    public function show($id)
    {
        $ticket = Ticket::with(['user', 'assignedTo', 'replies.user', 'attachments'])->findOrFail($id);
        return view('admin.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
    
        $request->validate([
            'message' => 'required|string',
            'attachments.*' => 'file|max:10240',
        ]);
    
        // Step 1: Create the reply
        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);
    
        // Step 2: Attach files to the reply
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('tickets', 'public');
    
                TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'reply_id'  => $reply->id,
                    'filename'  => $file->getClientOriginalName(),
                    'filepath'  => $path,
                    'mime_type' => $file->getMimeType(),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }
    
        return back()->with('success', 'Reply sent.');
    }


    public function close($id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->status = 'closed';
        $ticket->closed_at = now();
        $ticket->save();

        return back()->with('success', 'Ticket closed.');
    }
}
