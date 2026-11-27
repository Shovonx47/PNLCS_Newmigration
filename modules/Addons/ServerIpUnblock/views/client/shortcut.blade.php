<div class="card mb-4">
    <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap">
        <div class="mr-3">
            <strong><i class="fas fa-shield-alt fa-fw"></i> {{ 'Connection / Firewall IP Unblocker' }}</strong>
            <div class="text-muted small mt-1">
                {{ 'Having trouble connecting from ' }}<code>{{ $visitorIp }}</code>{{ '? Check and clear firewall blocks across your ' . $count . ' eligible ' . ($count === 1 ? 'service' : 'services') . '.' }}
            </div>
        </div>
        <a href="{{ route('client.unblock-ip.index') }}" class="btn btn-default btn-sm mt-2 mt-sm-0">{{ 'Open IP Unblocker' }}</a>
    </div>
</div>
