@extends('client.layouts.twentyone')
@section('title', __('client.tickets.open_support_ticket'))

@section('content')
<form method="POST" action="{{ route('client.tickets.store') }}" enctype="multipart/form-data" role="form">
    @csrf

    <div class="card">
        <div class="card-body extra-padding">

            <h3 class="card-title">{{ __('client.tickets.new_support_request') }}</h3>

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    @foreach($errors->all() as $validationError)
                        <div>{{ $validationError }}</div>
                    @endforeach
                </div>
            @endif

            <p class="text-muted">{{ __('client.tickets.team_respond') }}</p>

            <div class="row">
                <div class="form-group col-md-4">
                    <label for="inputName">Name</label>
                    <input type="text" id="inputName" value="{{ auth()->user()->full_name }}" class="form-control disabled" disabled="disabled">
                </div>
                <div class="form-group col-md-5">
                    <label for="inputEmail">Email Address</label>
                    <input type="email" id="inputEmail" value="{{ auth()->user()->email }}" class="form-control disabled" disabled="disabled">
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-10">
                    <label for="inputSubject">{{ __('common.form.subject') }}</label>
                    <input type="text" name="subject" id="inputSubject" value="{{ old('subject') }}" class="form-control" required placeholder="{{ __('client.tickets.subject_placeholder') }}">
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-3">
                    <label for="inputDepartment">{{ __('client.contact.department') }}</label>
                    <select name="department_id" id="inputDepartment" class="form-control" required>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if(isset($services) && $services->isNotEmpty())
                <div class="form-group col-md-5">
                    <label for="inputRelatedService">{{ __('client.tickets.related_service') }}</label>
                    <select name="related_service" id="inputRelatedService" class="form-control">
                        <option value="">{{ __('client.tickets.none') }}</option>
                        @foreach($services as $svc)
                            @php($svcLabel = implode(' · ', array_filter([$svc->domain, $svc->primaryIp()])))
                            <option value="{{ $svc->id }}" {{ old('related_service') == $svc->id ? 'selected' : '' }}>
                                {{ $svc->product?->name ?? __('client.services.title') }}{{ $svcLabel !== '' ? ' (' . $svcLabel . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="form-group col-md-3">
                    <label for="inputPriority">{{ __('client.tickets.priority') }}</label>
                    <select name="priority" id="inputPriority" class="form-control">
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>{{ __('client.tickets.high') }}</option>
                        <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>{{ __('client.tickets.medium') }}</option>
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>{{ __('client.tickets.low') }}</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="inputMessage">{{ __('common.form.message') }}</label>
                <textarea name="message" id="inputMessage" rows="12" class="form-control markdown-editor" required placeholder="{{ __('client.tickets.message_placeholder') }}">{{ old('message') }}</textarea>
            </div>

            <div class="form-group">
                <label for="inputAttachment1">{{ __('client.tickets.attachment') }}</label>
                <div class="input-group mb-1 attachment-group">
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" name="attachment" id="inputAttachment1" accept=".jpg,.png,.gif,.pdf,.doc,.docx,.txt,.zip">
                        <label class="custom-file-label text-truncate" for="inputAttachment1">Choose File</label>
                    </div>
                </div>
                <div class="text-muted">
                    <small>Allowed File Extensions: .jpg, .png, .gif, .pdf, .doc, .docx, .txt, .zip ({{ __('client.tickets.max_10mb') }})</small>
                </div>
            </div>

            <div class="text-center">
                @include('client.partials.recaptcha', ['form' => 'tickets'])
            </div>

            <p class="text-center">
                <button type="submit" id="openTicketSubmit" class="btn btn-primary">{{ __('client.tickets.submit_ticket') }}</button>
                <a href="{{ route('client.tickets.index') }}" class="btn btn-default">{{ __('common.actions.cancel') }}</a>
            </p>

        </div>
    </div>
</form>

{{-- The theme clears the file input's label when a file is chosen; with no
     theme scripts loaded, do that much here. --}}
<script>
document.querySelectorAll('.custom-file-input').forEach(function (input) {
    input.addEventListener('change', function () {
        var label = input.nextElementSibling;
        if (label && input.files.length) label.textContent = input.files[0].name;
    });
});
</script>
@endsection
