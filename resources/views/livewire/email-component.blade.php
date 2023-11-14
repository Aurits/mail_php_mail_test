{{-- resources/views/livewire/email-component.blade.php --}}
<div>
    <h1>Emails</h1>
    @if ($emails !== null && count($emails) > 0)
        <ul>
            @foreach ($emails as $email)
                <li>{{ $email }}</li>
            @endforeach
        </ul>
    @else
        <p>No emails available.</p>
    @endif

    @error('fetchEmails')
        <p class="text-red-500">{{ $message }}</p>
    @enderror
</div>
@livewireScripts

