<div id="sc-sms-otp" style="display:none;margin-top:10px;padding:12px 14px;border:1px solid #cbd5e1;border-radius:6px;background:#f8fafc;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
        <strong style="font-size:13px;color:#0f172a;">
            <span style="color:#2563eb;">&#9993;</span>
            মোবাইল নম্বর SMS OTP ভেরিফিকেশন <span style="color:#64748b;font-weight:400;">(Phone verification)</span>
        </strong>
        <span id="sc-sms-badge" style="font-size:11px;font-weight:700;padding:2px 9px;border-radius:12px;background:#fef3c7;color:#92400e;">Required</span>
    </div>

    <div id="sc-sms-body">
        <p style="font-size:12px;color:#475569;margin:8px 0 10px;">
            অ্যাকাউন্ট তৈরি করার আগে আপনার মোবাইলে পাঠানো কোডটি দিন।
            (Enter the code sent to your mobile number.)
        </p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <button type="button" id="sc-sms-send" class="btn btn-primary btn-sm">Send SMS OTP</button>
            <div id="sc-sms-verify-group" style="display:none;gap:8px;align-items:center;">
                <input type="text" id="sc-sms-code" inputmode="numeric" autocomplete="one-time-code"
                       maxlength="{{ $length }}" placeholder="{{ $length }}-digit code"
                       style="width:150px;padding:6px 10px;border:1px solid #94a3b8;border-radius:4px;font-size:14px;letter-spacing:2px;text-align:center;">
                <button type="button" id="sc-sms-verify" class="btn btn-success btn-sm">Verify</button>
            </div>
        </div>
    </div>

    <div id="sc-sms-verified" style="display:none;font-size:13px;color:#065f46;font-weight:600;">
        <span style="color:#10b981;">&#10003;</span> মোবাইল নম্বর ভেরিফাই হয়েছে (<span id="sc-sms-verified-number"></span>)।
        <button type="button" id="sc-sms-change" class="btn btn-link btn-xs" style="padding:0;margin-left:6px;">Change number</button>
    </div>

    <div id="sc-sms-msg" style="display:none;margin-top:8px;font-size:12.5px;font-weight:600;padding:7px 10px;border-radius:4px;"></div>

{{-- The fields above say "Phone (optional)" on their own. With the SMS gate
     switched on the number is required, so say that here rather than letting
     the customer fill the rest of the form and be refused at the end. --}}
<script>
(function () {
    var phone = document.getElementById('phone_number');
    if (phone) { phone.required = true; phone.setAttribute('aria-required', 'true'); }

    var label = document.querySelector('label[for="phone_number"]');
    if (!label) return;
    Array.prototype.forEach.call(label.querySelectorAll('span'), function (span) {
        if (/^\(.+\)$/.test(span.textContent.trim())) { span.parentNode.removeChild(span); }
    });
    if (!label.querySelector('.sc-sms-req')) {
        var mark = document.createElement('span');
        mark.className = 'sc-sms-req';
        mark.style.color = '#c43c35';
        mark.textContent = '*';
        label.appendChild(mark);
    }
})();
</script>
</div>

<script>
(function () {
    var sendUrl = @json($sendUrl);
    var verifyUrl = @json($verifyUrl);
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var token = csrf ? csrf.getAttribute('content') : '';

    var phone = document.getElementById('phone_number');
    var prefix = document.getElementById('phone_prefix');
    var wrapper = document.getElementById('sc-sms-otp');

    if (! phone || ! wrapper) { return; }

    // The hook renders at the end of the page; the box belongs under the
    // phone field, where the customer is looking when they need it.
    var anchor = phone.closest('.form-group') || phone.parentNode;
    anchor.parentNode.insertBefore(wrapper, anchor.nextSibling);
    wrapper.style.display = 'block';

    var badge = document.getElementById('sc-sms-badge');
    var body = document.getElementById('sc-sms-body');
    var verifiedView = document.getElementById('sc-sms-verified');
    var verifiedNumber = document.getElementById('sc-sms-verified-number');
    var msg = document.getElementById('sc-sms-msg');
    var sendBtn = document.getElementById('sc-sms-send');
    var verifyGroup = document.getElementById('sc-sms-verify-group');
    var verifyBtn = document.getElementById('sc-sms-verify');
    var codeInput = document.getElementById('sc-sms-code');
    var changeBtn = document.getElementById('sc-sms-change');
    var isVerified = @json($verifiedPhone !== '');
    var cooldown = {{ (int) $cooldown }};
    var timer = null;

    function show(kind, text) {
        msg.style.display = 'block';
        msg.textContent = text;
        msg.style.background = kind === 'error' ? '#fee2e2' : (kind === 'success' ? '#d1fae5' : '#dbeafe');
        msg.style.color = kind === 'error' ? '#991b1b' : (kind === 'success' ? '#065f46' : '#1e40af');
    }

    function locked() {
        badge.textContent = 'Verified';
        badge.style.background = '#d1fae5';
        badge.style.color = '#065f46';
        body.style.display = 'none';
        verifiedView.style.display = 'block';
        phone.readOnly = true;
        phone.style.background = '#f0fdf4';
        msg.style.display = 'none';
        isVerified = true;
    }

    function unlocked() {
        isVerified = false;
        badge.textContent = 'Required';
        badge.style.background = '#fef3c7';
        badge.style.color = '#92400e';
        body.style.display = 'block';
        verifiedView.style.display = 'none';
        phone.readOnly = false;
        phone.style.background = '';
    }

    function startCooldown(seconds) {
        if (timer) { clearInterval(timer); }
        var left = parseInt(seconds, 10) || 0;
        if (left <= 0) { return; }
        sendBtn.disabled = true;
        var tick = function () {
            sendBtn.textContent = 'Resend (' + left + 's)';
            left--;
            if (left < 0) {
                clearInterval(timer);
                timer = null;
                sendBtn.disabled = false;
                sendBtn.textContent = 'Resend SMS OTP';
            }
        };
        tick();
        timer = setInterval(tick, 1000);
    }

    function post(url, payload) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        }).then(function (r) { return r.json(); });
    }

    if (isVerified) {
        verifiedNumber.textContent = '+' + @json($verifiedPhone);
        locked();
    }

    sendBtn.addEventListener('click', function () {
        var value = (phone.value || '').trim();
        if (value.replace(/\D/g, '').length < 10) {
            show('error', 'অনুগ্রহ করে সঠিক মোবাইল নম্বর লিখুন। (Enter a valid mobile number.)');
            phone.focus();
            return;
        }
        sendBtn.disabled = true;
        sendBtn.textContent = 'Sending...';
        post(sendUrl, { phone: value, prefix: prefix ? prefix.value : '' }).then(function (res) {
            if (res.status === 'success') {
                show('success', res.message);
                verifyGroup.style.display = 'flex';
                codeInput.value = '';
                codeInput.focus();
                startCooldown(res.cooldown || cooldown);
                sendBtn.textContent = 'Resend SMS OTP';
            } else {
                show('error', res.message || 'The code could not be sent.');
                sendBtn.textContent = 'Send SMS OTP';
                if (res.cooldown) { startCooldown(res.cooldown); } else { sendBtn.disabled = false; }
            }
        }).catch(function () {
            show('error', 'Connection failed. Please try again.');
            sendBtn.disabled = false;
            sendBtn.textContent = 'Send SMS OTP';
        });
    });

    verifyBtn.addEventListener('click', function () {
        var code = (codeInput.value || '').trim();
        if (code.length < 4) {
            show('error', 'Enter the code from the SMS.');
            codeInput.focus();
            return;
        }
        verifyBtn.disabled = true;
        post(verifyUrl, { phone: phone.value, prefix: prefix ? prefix.value : '', otp: code }).then(function (res) {
            verifyBtn.disabled = false;
            if (res.status === 'success') {
                verifiedNumber.textContent = '+' + (res.phone || '');
                locked();
            } else {
                show('error', res.message || 'Wrong code.');
                codeInput.focus();
            }
        }).catch(function () {
            verifyBtn.disabled = false;
            show('error', 'Verification failed. Please try again.');
        });
    });

    codeInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); verifyBtn.click(); }
    });

    changeBtn.addEventListener('click', function () {
        unlocked();
        verifyGroup.style.display = 'none';
        codeInput.value = '';
        phone.focus();
    });

    phone.addEventListener('input', function () {
        if (isVerified) { unlocked(); }
    });
})();
</script>
