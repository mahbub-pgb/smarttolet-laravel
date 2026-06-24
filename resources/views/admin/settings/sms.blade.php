@extends('admin.layout')

@section('title', 'SMS Settings')
@section('heading', 'Bulk SMS Settings')

@section('content')
    @php($apiKey = $settings['sms_api_key'] ?? ['configured' => false])

    <div class="admin-cols">
        <section class="panel" style="max-width:560px">
            <h3>Gateway credentials</h3>
            <p class="muted" style="margin-top:-6px">Used to deliver signup OTPs and notifications. Credentials are stored securely and override the <code>.env</code> defaults.</p>

            <form method="POST" action="{{ route('admin.settings.sms.update') }}">
                @csrf
                <div class="field">
                    <label>SMS provider</label>
                    <select name="sms_provider">
                        <option value="mock" @selected(($settings['sms_provider'] ?? 'mock') === 'mock')>Mock (log only — for testing)</option>
                        <option value="bulksmsbd" @selected(($settings['sms_provider'] ?? '') === 'bulksmsbd')>BulkSMSBD</option>
                    </select>
                    @error('sms_provider')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label>Sender ID</label>
                    <input type="text" name="sms_sender_id" value="{{ old('sms_sender_id', $settings['sms_sender_id'] ?? '') }}" placeholder="SmartToLet">
                    @error('sms_sender_id')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label>
                        API key
                        @if (!empty($apiKey['configured']))
                            <span class="pill" style="background:#dcfce7;color:#166534">configured ✓</span>
                        @else
                            <span class="pill" style="background:#fef9c3;color:#854d0e">not set</span>
                        @endif
                    </label>
                    <input type="password" name="sms_api_key" placeholder="{{ !empty($apiKey['configured']) ? 'Leave blank to keep current key' : 'Paste your API key' }}" autocomplete="off">
                    <p class="muted" style="font-size:0.8rem;margin:6px 0 0">For security the saved key is never shown. Leave blank to keep it.</p>
                    @error('sms_api_key')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn">Save settings</button>
            </form>
        </section>

        <section class="panel" style="max-width:360px;align-self:start">
            <h3>Send a test SMS</h3>
            <p class="muted" style="margin-top:-6px">Verify your gateway works by sending one message.</p>
            <form method="POST" action="{{ route('admin.settings.sms.test') }}">
                @csrf
                <div class="field">
                    <label>Phone number</label>
                    <input type="tel" name="test_number" value="{{ old('test_number') }}" placeholder="01712345678">
                    @error('test_number')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-accent btn-block">Send test message</button>
            </form>
        </section>
    </div>
@endsection
