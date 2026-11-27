@extends('client.layouts.twentyone')
@section('title', __('client.account.change_password'))
@section('content')

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="card-title">{{ __('client.password.update_password') }}</h3>
                <p class="text-muted">{{ __('client.password.page_subtitle') }}</p>

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 pl-3">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="using-password-strength" method="POST" action="{{ route('client.account.password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group row">
                        <label class="col-xl-4 col-form-label" for="current_password">
                            {{ __('common.form.current_password') }} <span class="text-danger">*</span>
                        </label>
                        <div class="col-xl-8">
                            <input type="password" id="current_password" name="current_password" required class="form-control" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-xl-4 col-form-label" for="password">
                            {{ __('common.form.new_password') }} <span class="text-danger">*</span>
                        </label>
                        <div class="col-xl-8">
                            <input type="password" id="password" name="password" required class="form-control" autocomplete="off"
                                   oninput="checkStrength(this.value)" placeholder="{{ __('client.password.min_chars') }}">
                            <div class="progress mt-2">
                                <div class="progress-bar" role="progressbar" id="pwBar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="form-text text-muted" id="pwHint">{{ __('client.password.enter_new') }}</small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-xl-4 col-form-label" for="password_confirmation">
                            {{ __('client.password.confirm_new') }} <span class="text-danger">*</span>
                        </label>
                        <div class="col-xl-8">
                            <input type="password" id="password_confirmation" name="password_confirmation" required class="form-control" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group row mb-0">
                        <div class="col-xl-8 offset-xl-4">
                            <button type="submit" class="btn btn-primary">{{ __('client.password.update_btn') }}</button>
                            <button type="reset" class="btn btn-default ml-1">{{ __('common.actions.cancel') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title h6 mb-0">{{ __('client.password.tips_title') }}</h4>
            </div>
            <div class="card-body">
                <ol class="pl-3 mb-0 text-muted small">
                    <li class="mb-2">{{ __('client.password.tip_1') }}</li>
                    <li class="mb-2">{{ __('client.password.tip_2') }}</li>
                    <li class="mb-2">{{ __('client.password.tip_3') }}</li>
                    <li class="mb-2">{{ __('client.password.tip_4') }}</li>
                    <li>{{ __('client.password.tip_5') }}</li>
                </ol>
            </div>
        </div>

        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-lock fa-2x text-muted mb-2"></i>
                <div class="small text-muted">
                    {{ __('client.password.last_changed') }}<br>
                    <strong class="text-body">{{ __('client.password.never') }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function checkStrength(v) {
    var bar = document.getElementById("pwBar"), hint = document.getElementById("pwHint");
    if (!bar || !hint) return;
    var score = 0;
    if (v.length >= 8) score++;
    if (v.length >= 12) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    var levels = [
        {w:"0%", cls:"bg-danger", txt:"text-danger", t:@json(__('client.password.too_short'))},
        {w:"20%", cls:"bg-danger", txt:"text-danger", t:@json(__('client.password.very_weak'))},
        {w:"40%", cls:"bg-warning", txt:"text-warning", t:@json(__('client.password.weak'))},
        {w:"60%", cls:"bg-warning", txt:"text-warning", t:@json(__('client.password.fair'))},
        {w:"80%", cls:"bg-info", txt:"text-info", t:@json(__('client.password.strong'))},
        {w:"100%", cls:"bg-success", txt:"text-success", t:@json(__('client.password.very_strong'))}
    ];
    var l = levels[Math.min(score, 5)];
    bar.style.width = l.w;
    bar.className = "progress-bar " + l.cls;
    hint.textContent = l.t;
    hint.className = "form-text font-weight-bold " + l.txt;
}
</script>
@endsection
