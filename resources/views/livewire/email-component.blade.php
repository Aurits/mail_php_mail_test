{{-- resources/views/livewire/email-component.blade.php --}}
<div>
    <h1>Emails</h1>
    <ul>
        @foreach ($emails as $email)
            <li>{{ $email }}</li>
        @endforeach
    </ul>
</div>
