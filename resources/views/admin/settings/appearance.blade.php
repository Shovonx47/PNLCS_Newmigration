@extends("admin.layouts.app")
@section("title", __("admin.settings.appearance"))

@section("content")
<h1>{{ __('admin.settings.appearance_settings') }}</h1>

{{-- TAB NAVIGATION --}}
<ul class="nav nav-tabs admin-tabs" role="tablist">
    <li class="nav-item active"><a href="#themes" class="nav-link active appearance-tab" data-tab="themes" onclick="switchTab('themes', this); return false;"><i class="fas fa-swatchbook"></i> {{ __('admin.appearance.tab_themes') }}</a></li>
    <li class="nav-item"><a href="#colors" class="nav-link appearance-tab" data-tab="colors" onclick="switchTab('colors', this); return false;"><i class="fas fa-tint"></i> {{ __('admin.appearance.tab_colors') }}</a></li>
    <li class="nav-item"><a href="#builder" class="nav-link appearance-tab" data-tab="builder" onclick="switchTab('builder', this); return false;"><i class="fas fa-puzzle-piece"></i> {{ __('admin.appearance.tab_builder') }}</a></li>
    <li class="nav-item"><a href="#whitelabel" class="nav-link appearance-tab" data-tab="whitelabel" onclick="switchTab('whitelabel', this); return false;"><i class="fas fa-tag"></i> {{ __('admin.appearance.tab_whitelabel') }}</a></li>
    <li class="nav-item"><a href="#darkmode" class="nav-link appearance-tab" data-tab="darkmode" onclick="switchTab('darkmode', this); return false;"><i class="fas fa-moon"></i> {{ __('admin.appearance.tab_darkmode') }}</a></li>
</ul>

<style>
    .tab-pane { display: none; }
    .tab-pane.active { display: block; }
    .theme-card { border: 1px solid #dcdcdc; border-radius: 4px; overflow: hidden; position: relative; background: #fff; }
    .theme-card.active { border: 2px solid #1a4d80; }
    .theme-card__screenshot { width: 100%; height: 160px; object-fit: cover; background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .theme-card__screenshot img { width: 100%; height: 100%; object-fit: cover; }
    .theme-card__screenshot--placeholder { color: #94a3b8; font-size: 40px; }
    .theme-card__body { padding: 12px; }
    .theme-card__badge { position: absolute; top: 8px; right: 8px; background: #1a4d80; color: #fff; font-size: 10px; padding: 2px 8px; border-radius: 3px; font-weight: 700; text-transform: uppercase; }
    .theme-card__badge--builtin { background: #555; }
    .theme-upload { border: 2px dashed #ccc; border-radius: 4px; padding: 30px; text-align: center; cursor: pointer; }
    .theme-upload:hover { border-color: #1a4d80; background: #f8fafc; }
</style>

<div class="tab-content admin-tabs">
{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- TAB 1: THEMES --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="tab-pane active" id="tab-themes">
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3" style="margin-bottom:16px;">
        <tr>
            <td class="fieldlabel" width="20%">{{ __('admin.appearance.logo') }}</td>
            <td class="fieldarea">
                @if($logoPath)
                <div style="margin-bottom:8px;padding:8px;background:#f9f9f9;border:1px solid #eee;display:inline-block;">
                    <img src="{{ $logoPath }}" alt="Logo" style="max-height:50px;max-width:200px;">
                </div>
                <form action="{{ route('admin.settings.appearance.logo.remove') }}" method="POST" style="display:inline-block;margin-left:8px;">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i> {{ __('admin.appearance.remove_logo') }}</button>
                </form>
                @else
                <div style="color:#777;font-size:12px;margin-bottom:6px;">{{ __('admin.appearance.no_logo') }}</div>
                @endif
                <form action="{{ route('admin.settings.appearance.logo') }}" method="POST" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    @csrf
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control" style="max-width:300px;">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-upload"></i> {{ __('admin.appearance.upload_logo') }}</button>
                    <span style="font-size:11px;color:#777;">{{ __('admin.appearance.logo_formats') }}</span>
                </form>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.appearance.favicon') }}</td>
            <td class="fieldarea">
                @if($faviconPath)
                <div style="margin-bottom:8px;display:inline-flex;align-items:center;gap:8px;">
                    <img src="{{ $faviconPath }}" alt="Favicon" style="width:24px;height:24px;">
                    <span style="font-size:12px;color:#555;">{{ __('admin.appearance.current_favicon') }}</span>
                </div>
                <form action="{{ route('admin.settings.appearance.favicon.remove') }}" method="POST" style="display:inline-block;margin-left:8px;">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i> {{ __('admin.appearance.remove_favicon') }}</button>
                </form>
                @else
                <div style="color:#777;font-size:12px;margin-bottom:6px;">{{ __('admin.appearance.no_favicon') }}</div>
                @endif
                <form action="{{ route('admin.settings.appearance.favicon') }}" method="POST" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    @csrf
                    <input type="file" name="favicon" accept="image/png,image/x-icon,.ico" class="form-control" style="max-width:300px;">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-upload"></i> {{ __('admin.appearance.upload_favicon') }}</button>
                    <span style="font-size:11px;color:#777;">{{ __('admin.appearance.favicon_formats') }}</span>
                </form>
            </td>
        </tr>
    </table>

    <h2 style="font-size:15px;margin:16px 0 8px;">{{ __('admin.appearance.installed_themes') }} <span style="font-size:12px;font-weight:400;color:#777;">({{ count($installedThemes) }} {{ __('admin.appearance.themes_installed') }})</span></h2>
    <p style="margin-bottom:12px;color:#666;font-size:13px;">{{ __('admin.appearance.theme_description') }}</p>

    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:14px; margin-bottom:16px;">
        @foreach($installedThemes as $slug => $themeInfo)
        <div class="theme-card {{ $themeInfo->isActive ? 'active' : '' }}">
            @if($themeInfo->isActive)
            <div class="theme-card__badge">{{ __('admin.appearance.active_badge') }}</div>
            @elseif($themeInfo->isBuiltin)
            <div class="theme-card__badge theme-card__badge--builtin">{{ __('admin.appearance.built_in_badge') }}</div>
            @endif

            <div class="theme-card__screenshot">
                @if($themeInfo->screenshot)
                <img src="{{ $themeInfo->screenshot }}" alt="{{ $themeInfo->name }}">
                @else
                <div class="theme-card__screenshot--placeholder"><i class="fas fa-image"></i></div>
                @endif
            </div>

            <div class="theme-card__body">
                <h3 style="font-size:14px; font-weight:700; margin:0 0 4px;">{{ $themeInfo->name }}</h3>
                <p style="font-size:11px; color:#777; margin-bottom:4px;">{{ __('admin.appearance.theme_by', ['version' => $themeInfo->version, 'author' => $themeInfo->author]) }}</p>
                <p style="font-size:12px; color:#777; margin-bottom:10px; line-height:1.4;">{{ Str::limit($themeInfo->description, 80) }}</p>

                @if(!empty($themeInfo->colors))
                <div style="display:flex; gap:4px; margin-bottom:10px;">
                    @foreach(array_slice($themeInfo->colors, 0, 6) as $colorKey => $colorVal)
                    <div title="{{ $colorKey }}: {{ $colorVal }}" style="width:20px; height:20px; border-radius:3px; background:{{ $colorVal }}; border:1px solid rgba(0,0,0,0.15);"></div>
                    @endforeach
                </div>
                @endif

                @if($themeInfo->isActive)
                <button class="btn btn-xs btn-default" style="width:100%;" disabled>{{ __('admin.appearance.currently_active') }}</button>
                @else
                <div style="display:flex; gap:6px;">
                    <form action="{{ route('admin.settings.appearance.theme.activate') }}" method="POST" style="flex:1;">
                        @csrf
                        <input type="hidden" name="slug" value="{{ $slug }}">
                        <button type="submit" class="btn btn-xs btn-primary" style="width:100%;">
                            <i class="fas fa-check"></i> {{ __('common.actions.activate') }}</button>
                    </form>
                    <a href="{{ route('admin.settings.appearance.theme.download', $slug) }}" class="btn btn-xs btn-default" title="{{ __('admin.appearance.download_zip') }}"><i class="fas fa-download"></i></a>
                    @if(!$themeInfo->isBuiltin)
                    <form action="{{ route('admin.settings.appearance.theme.delete', $slug) }}" method="POST" onsubmit="return pnConfirm(event, @js($themeInfo->name), {title: @js(__('admin.appearance.confirm_delete_theme')), danger: true});">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                    </form>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endforeach

        @if(auth('admin')->user()?->role?->is_full_admin)
        <div class="theme-card" style="border-style:dashed;">
            <form action="{{ route('admin.settings.appearance.theme.install') }}" method="POST" enctype="multipart/form-data" id="themeUploadForm">
                @csrf
                <label class="theme-upload" for="themeZipInput" style="height:100%; min-height:240px; display:flex; flex-direction:column; align-items:center; justify-content:center; margin:0; border:none;">
                    <i class="fas fa-cloud-upload-alt" style="font-size:36px; color:#999; margin-bottom:12px;"></i>
                    <span style="font-size:13px; font-weight:700; color:#555; margin-bottom:4px;">{{ __('admin.appearance.upload_theme') }}</span>
                    <span style="font-size:11px; color:#888; margin-bottom:12px;">{{ __('admin.appearance.theme_zip_hint') }}</span>
                    <input type="file" name="theme_zip" id="themeZipInput" accept=".zip" style="display:none;" onchange="document.getElementById('themeUploadForm').submit();">
                    <span class="btn btn-xs btn-default" style="pointer-events:none;"><i class="fas fa-folder-open"></i> {{ __('admin.appearance.choose_file') }}</span>
                </label>
            </form>
        </div>
        @endif
    </div>

    @if(!empty($installedThemes))
    <div class="infobox">
        <strong>{{ __('admin.appearance.themes_how_work') }}</strong> {{ __('admin.appearance.themes_how_work_desc') }}
    </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- TAB 2: COLOR PRESETS & CUSTOM TOKENS --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="tab-pane" id="tab-colors">
    <h2 style="font-size:15px;margin:0 0 6px;">{{ __('admin.appearance.color_presets') }}</h2>
    <p style="margin-bottom:12px; color:#666; font-size:13px;">{{ __('admin.appearance.preset_description') }}</p>
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:20px;">
        @foreach($presets as $key => $preset)
        <div style="border:{{ $activePreset === $key ? '2px solid #1a4d80' : '1px solid #dcdcdc' }}; border-radius:4px; padding:14px; position:relative; background:#fff;">
            @if($activePreset === $key)
            <span class="whmcs-status active" style="position:absolute; top:8px; right:8px;">{{ __('admin.appearance.active_badge') }}</span>
            @endif
            <h3 style="font-size:14px; font-weight:700; margin:0 0 4px;">{{ $preset['name'] }}</h3>
            <p style="font-size:12px; color:#777; margin-bottom:10px;">{{ $preset['description'] }}</p>
            <div style="display:flex; gap:4px; margin-bottom:12px; flex-wrap:wrap;">
                @foreach(['primary','accent','nav_bg','sidebar_bg','welcome_accent','table_header_bg'] as $swatch)
                <div title="{{ $swatch }}: {{ $preset['colors'][$swatch] }}" style="width:24px; height:24px; border-radius:3px; background:{{ $preset['colors'][$swatch] }}; border:1px solid rgba(0,0,0,0.15);"></div>
                @endforeach
            </div>
            @if($activePreset !== $key)
            <form action="{{ route('admin.settings.appearance.update') }}" method="POST">
                @csrf
                <input type="hidden" name="preset" value="{{ $key }}">
                <button type="submit" class="btn btn-xs btn-primary" style="width:100%;">{{ __('admin.appearance.activate_preset', ['name' => $preset['name']]) }}</button>
            </form>
            @else
            <button class="btn btn-xs btn-default" style="width:100%;" disabled>{{ __('admin.appearance.currently_active') }}</button>
            @endif
        </div>
        @endforeach
    </div>

    <h2 style="font-size:15px;margin:0 0 6px;">{{ __('admin.appearance.custom_colors_tokens') }} ({{ __('admin.appearance.tokens_count', ['count' => count($tokenLabels)]) }})</h2>
    <p style="margin-bottom:12px; color:#666; font-size:13px;">{{ __('admin.appearance.custom_tokens_description') }}</p>
    <form action="{{ route('admin.settings.appearance.update') }}" method="POST" id="customColorForm">
        @csrf
        <input type="hidden" name="preset" value="custom">

        @foreach($tokenGroups as $groupName => $keys)
        <details style="margin-bottom:10px; border:1px solid #dcdcdc; border-radius:3px; background:#fff;" {{ $groupName === 'Colors' ? 'open' : '' }}>
            <summary style="padding:8px 12px; font-weight:700; font-size:13px; cursor:pointer; background:#eef2f7;">
                {{ $groupName }} ({{ __('admin.appearance.tokens_count', ['count' => count($keys)]) }})
            </summary>
            <div style="padding:12px; display:grid; grid-template-columns:repeat(4,1fr); gap:10px;">
                @foreach($keys as $key)
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; color:#555; margin-bottom:3px;">{{ $tokenLabels[$key] ?? $key }}</label>
                    <div style="display:flex; align-items:center; gap:6px;">
                        @if(in_array($key, $colorKeys))
                        <input type="color" name="colors[{{ $key }}]" value="{{ $activeColors[$key] ?? '#000000' }}"
                               style="width:34px; height:26px; border:1px solid #ccc; padding:0; cursor:pointer;"
                               onchange="document.getElementById('hex_{{ $key }}').value = this.value">
                        <input type="text" id="hex_{{ $key }}" value="{{ $activeColors[$key] ?? '#000000' }}"
                               style="width:80px; padding:3px 6px; font-size:11px; border:1px solid #ccc; font-family:monospace;"
                               onchange="let v=this.value; if(/^#[0-9a-fA-F]{6}$/.test(v)){this.previousElementSibling.value=v; document.querySelector('input[name=\'colors[{{ $key }}]\']').value=v}">
                        @else
                        <input type="text" name="colors[{{ $key }}]" value="{{ $activeColors[$key] ?? '' }}"
                               style="width:100%; padding:3px 6px; font-size:12px; border:1px solid #ccc; font-family:monospace;">
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </details>
        @endforeach

        <div class="btn-container">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ __('admin.appearance.save_custom_tokens') }}</button>
            <button type="button" class="btn btn-default" onclick="window.location.reload()"><i class="fas fa-undo"></i> {{ __('admin.appearance.reset_to_preset') }}</button>
        </div>
    </form>

    <h2 style="font-size:15px;margin:20px 0 8px;">{{ __('admin.appearance.live_preview') }}</h2>
    <div id="themePreview" style="border:1px solid #dcdcdc;padding:14px;background:#fff;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div>
                <p style="font-size:11px; font-weight:700; color:#777; text-transform:uppercase; margin-bottom:8px;">{{ __('admin.appearance.nav_sidebar_preview') }}</p>
                <div style="border:1px solid #ddd;">
                    <div id="prev-nav" style="background:{{ $activeColors['nav_bg'] }}; padding:10px 16px; color:#fff; font-weight:700; font-size:13px;">
                        {{ 'PNLCS' }} <span style="font-weight:400; opacity:0.7; margin-left:12px;">{{ __('admin.appearance.preview_dashboard') }}</span>
                    </div>
                    <div style="display:flex;">
                        <div id="prev-sidebar" style="background:{{ $activeColors['sidebar_bg'] }}; width:120px; padding:10px;">
                            <div style="font-size:11px; color:{{ $activeColors['sidebar_text'] }};">{{ __("admin.appearance.preview_menu_item_1") }}</div>
                            <div style="font-size:11px; color:{{ $activeColors['sidebar_text'] }}; margin-top:4px;">{{ __("admin.appearance.preview_menu_item_2") }}</div>
                        </div>
                        <div style="flex:1; padding:10px; background:#fff;">
                            <div id="prev-btn" style="display:inline-block; padding:4px 12px; background:{{ $activeColors['primary'] }}; color:#fff; border-radius:3px; font-size:11px; font-weight:600;">{{ __("admin.appearance.preview_button") }}</div>
                        </div>
                    </div>
                    <div id="prev-footer" style="background:{{ $activeColors['footer_bg'] }}; padding:6px 16px; color:#fff; font-size:10px; opacity:0.8;">{{ __("admin.appearance.preview_footer") }}</div>
                </div>
            </div>
            <div>
                <p style="font-size:11px; font-weight:700; color:#777; text-transform:uppercase; margin-bottom:8px;">{{ __('admin.appearance.welcome_hero_preview') }}</p>
                <div style="border:1px solid #ddd;">
                    <div id="prev-hero" style="background:linear-gradient(135deg, {{ $activeColors['hero_bg_start'] }}, {{ $activeColors['hero_bg_mid'] }}, {{ $activeColors['hero_bg_end'] }}); padding:28px; text-align:center;">
                        <div style="color:#fff; font-size:18px; font-weight:800; margin-bottom:8px;">{{ __("admin.appearance.preview_your_website") }}</div>
                        <div id="prev-cta" style="display:inline-block; padding:6px 18px; background:{{ $activeColors['welcome_accent'] }}; color:#fff; border-radius:4px; font-size:11px; font-weight:700;">{{ __('admin.appearance.preview_cta') }}</div>
                    </div>
                    <div style="padding:12px; background:#fff;">
                        <div id="prev-table-header" style="background:{{ $activeColors['table_header_bg'] }}; color:#fff; padding:4px 10px; font-size:11px; font-weight:600;">{{ __("admin.appearance.preview_table_header") }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- TAB 3: HOMEPAGE BUILDER --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="tab-pane" id="tab-builder">
    <div class="context-btn-container" style="margin-bottom:10px;">
        <a href="/" target="_blank" class="btn btn-sm btn-default"><i class="fas fa-external-link-alt"></i> {{ __('common.actions.preview') }}</a>
    </div>
    <div class="infobox">{{ __('admin.appearance.sections_description') }}</div>

    <div class="tablebg">
        <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
            <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th>{{ __('admin.appearance.homepage_sections') }}</th>
                    <th>{{ __('common.table.status') }}</th>
                    <th style="text-align:right;width:120px;">{{ __('common.table.actions') }}</th>
                </tr>
            </thead>
            <tbody id="sectionList">
                @foreach($sections as $section)
                <tr class="section-row" data-id="{{ $section->id }}" data-slug="{{ $section->slug }}">
                    <td style="text-align:center;"><span class="drag-handle" style="cursor:grab; color:#888;"><i class="fas fa-grip-vertical"></i></span></td>
                    <td>
                        <div style="font-weight:700;">{{ $section->title }}</div>
                        <div style="font-size:11px; color:#777;">{{ $section->slug }}</div>
                    </td>
                    <td>
                        <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; font-size:12px; font-weight:600; margin:0; color:{{ $section->is_enabled ? '#10b981' : '#ef4444' }};">
                            <input type="checkbox" class="section-toggle" data-id="{{ $section->id }}" {{ $section->is_enabled ? 'checked' : '' }}
                                   onchange="toggleSection({{ $section->id }}, this.checked)"
                                   style="cursor:pointer;">
                            {{ $section->is_enabled ? 'ON' : 'OFF' }}
                        </label>
                    </td>
                    <td style="text-align:right;">
                        <button type="button" class="btn btn-xs btn-primary" onclick="editSection('{{ $section->slug }}', '{{ $section->title }}')">
                            <i class="fas fa-edit"></i> {{ __('common.actions.edit') }}</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Section Edit Modal --}}
    <div id="sectionEditModal" style="display:none; position:fixed; inset:0; z-index:1000; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:4px; max-width:700px; width:90%; max-height:80vh; overflow-y:auto; padding:20px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">
                <h3 id="modalTitle" style="font-size:16px; font-weight:700; margin:0;">{{ __('admin.appearance.edit_section') }}</h3>
                <button type="button" onclick="closeSectionModal()" style="background:none; border:none; font-size:20px; cursor:pointer; color:#777;">&times;</button>
            </div>
            <div id="modalContent">
                <p style="color:#999;">{{ __("admin.appearance.preview_loading") }}</p>
            </div>
            <div class="btn-container" style="text-align:right;margin-bottom:0;">
                <button type="button" class="btn btn-default btn-sm" onclick="closeSectionModal()">{{ __('common.actions.cancel') }}</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveSectionContent()"><i class="fas fa-save"></i> {{ __('common.actions.save') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- TAB 4: WHITE-LABEL --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="tab-pane" id="tab-whitelabel">
    <div class="infobox">{{ __('admin.appearance.whitelabel_description') }}</div>
    <form action="{{ route('admin.settings.appearance.whitelabel') }}" method="POST">
        @csrf
        <input type="hidden" name="whitelabel_full_form" value="1">
        <input type="hidden" name="return_tab" value="whitelabel">
        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td class="fieldlabel" width="22%">{{ __('common.form.company_name') }}</td>
                <td class="fieldarea">
                    <input type="text" name="company_name" value="{{ $whitelabel['company_name'] }}" class="form-control" style="max-width:420px;" placeholder="{{ __('admin.appearance.company_placeholder') }}">
                    <div style="font-size:11px; color:#777; margin-top:3px;">{{ __('admin.appearance.replaces_hint') }}</div>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.appearance.company_url') }}</td>
                <td class="fieldarea"><input type="url" name="company_url" value="{{ $whitelabel['company_url'] }}" class="form-control" style="max-width:420px;" placeholder="https://myhosting.com"></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.appearance.support_email') }}</td>
                <td class="fieldarea"><input type="email" name="support_email" value="{{ $whitelabel['support_email'] }}" class="form-control" style="max-width:420px;" placeholder="support@myhosting.com"></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.appearance.copyright_text') }}</td>
                <td class="fieldarea">
                    <input type="text" name="copyright" value="{{ $whitelabel['copyright'] }}" class="form-control" style="max-width:420px;" placeholder="{{ __('admin.appearance.copyright_placeholder') }}">
                    <div style="font-size:11px; color:#777; margin-top:3px;">{{ __('admin.appearance.copyright_hint') }}</div>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.appearance.remove_branding') }}</td>
                <td class="fieldarea">
                    <label style="font-weight:400;margin:0;cursor:pointer;">
                        <input type="checkbox" name="remove_branding" value="1" {{ $whitelabel['remove_branding'] === '1' ? 'checked' : '' }}>
                        {{ __('admin.appearance.remove_branding') }}
                    </label>
                </td>
            </tr>
        </table>
        <div class="btn-container">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ __('admin.appearance.save_whitelabel') }}</button>
        </div>
    </form>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- TAB 5: DARK MODE --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="tab-pane" id="tab-darkmode">
    <div class="infobox">
        <strong>{{ __('admin.appearance.how_it_works') }}</strong>
        <ul style="font-size:12px; padding-left:20px; margin:6px 0 0;">
            <li>{{ __('admin.appearance.dark_toggle_note') }}</li>
            <li>{{ __("admin.appearance.darkmode_bullet_1") }}</li>
            <li>{{ __("admin.appearance.darkmode_bullet_2") }}</li>
            <li>{{ __("admin.appearance.darkmode_bullet_3") }}</li>
            <li>{{ __("admin.appearance.darkmode_bullet_4") }}</li>
        </ul>
    </div>
    <form action="{{ route('admin.settings.appearance.darkmode') }}" method="POST">
        @csrf
        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td class="fieldlabel" width="22%">{{ __('admin.appearance.dark_mode') }}</td>
                <td class="fieldarea">
                    <label style="font-weight:400;margin:0;cursor:pointer;">
                        <input type="checkbox" name="dark_mode_enabled" value="1" {{ $darkModeEnabled === '1' ? 'checked' : '' }}>
                        {{ __('admin.appearance.enable_darkmode') }}
                    </label>
                    <div style="font-size:12px; color:#777; margin-top:3px;">{{ __('admin.appearance.darkmode_description') }} {{ __('admin.appearance.darkmode_hint') }}</div>
                </td>
            </tr>
        </table>
        <div class="btn-container">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ __('admin.appearance.save_darkmode') }}</button>
        </div>
    </form>
</div>
</div>

@endsection

@push('scripts')
{{-- SortableJS for drag-drop --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
// Tab switching
function switchTab(tabName, btn) {
    const pane = document.getElementById('tab-' + tabName);
    if (!pane) { return; }
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.appearance-tab').forEach(t => {
        t.classList.remove('active');
        if (t.parentElement) t.parentElement.classList.remove('active');
    });
    pane.classList.add('active');
    const activeTab = btn || document.querySelector('.appearance-tab[data-tab="' + tabName + '"]');
    if (activeTab) {
        activeTab.classList.add('active');
        if (activeTab.parentElement) activeTab.parentElement.classList.add('active');
    }

    if (history.replaceState) {
        history.replaceState(null, '', '#' + tabName);
    }
}

// Open the tab the link, the redirect or a reload asked for. The setup
// checklist links straight at the field that is still missing; landing on the
// default tab instead is how someone ends up hunting for "company name".
document.addEventListener('DOMContentLoaded', function () {
    const wanted = (window.location.hash || '').replace('#', '')
        || @json(session('appearance_tab', ''));
    if (wanted) { switchTab(wanted, null); }
});

// SortableJS for section reorder
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('sectionList');
    if (el) {
        Sortable.create(el, {
            handle: '.drag-handle',
            animation: 200,
            onEnd: function() {
                const order = [];
                el.querySelectorAll('.section-row').forEach(row => order.push(parseInt(row.dataset.id)));
                fetch('{{ route("admin.settings.appearance.sections.reorder") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ order }),
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        el.style.outline = '2px solid #10b981';
                        setTimeout(() => el.style.outline = 'none', 500);
                    }
                });
            }
        });
    }
});

// Toggle section on/off
function toggleSection(id, enabled) {
    fetch('/admin/settings/appearance/sections/' + id, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ is_enabled: enabled ? 1 : 0 }),
    }).then(r => r.json()).then(data => {
        if (data.success) {
            const row = document.querySelector(`.section-row[data-id="${id}"]`);
            const label = row.querySelector('label');
            label.style.color = enabled ? '#10b981' : '#ef4444';
        }
    });
}

// Edit section content modal
let currentEditSlug = null;

function editSection(slug, title) {
    currentEditSlug = slug;
    document.getElementById('modalTitle').textContent = 'Edit: ' + title;
    document.getElementById('modalContent').innerHTML = '<p style="color:#999;">{{ __("admin.appearance.preview_loading") }}</p>';
    document.getElementById('sectionEditModal').style.display = 'flex';

    fetch('/admin/settings/appearance/sections/' + slug + '/content')
        .then(r => r.json())
        .then(data => {
            let html = '';
            if (data.content && data.content.length > 0) {
                data.content.forEach(item => {
                    const isJson = item.content_type === 'json';
                    const isHtml = item.content_type === 'html';
                    const rows = isJson ? 8 : (isHtml ? 3 : 1);
                    html += `<div style="margin-bottom:12px;">
                        <label style="display:block; font-size:12px; font-weight:700; color:#555; margin-bottom:3px;">
                            ${item.content_key}
                            <span style="font-size:10px; color:#999; font-weight:400;">(${item.content_type})</span>
                        </label>`;
                    if (rows > 1) {
                        html += `<textarea class="form-control content-field" data-key="${item.content_key}" data-type="${item.content_type}"
                                    rows="${rows}" style="font-family:monospace; font-size:12px;">${escapeHtml(item.content_value || '')}</textarea>`;
                    } else {
                        html += `<input type="text" class="form-control content-field" data-key="${item.content_key}" data-type="${item.content_type}"
                                    value="${escapeAttr(item.content_value || '')}" style="font-size:13px;">`;
                    }
                    html += '</div>';
                });
            } else {
                html = '<p style="color:#999;">No editable content for this section.</p>';
            }
            document.getElementById('modalContent').innerHTML = html;
        });
}

function closeSectionModal() {
    document.getElementById('sectionEditModal').style.display = 'none';
    currentEditSlug = null;
}

function saveSectionContent() {
    if (!currentEditSlug) return;
    const fields = document.querySelectorAll('#modalContent .content-field');
    const content = [];
    fields.forEach(f => {
        content.push({
            key: f.dataset.key,
            value: f.value,
            type: f.dataset.type || 'text',
        });
    });

    fetch('/admin/settings/appearance/sections/' + currentEditSlug + '/content', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ content }),
    }).then(r => r.json()).then(data => {
        if (data.success) {
            closeSectionModal();
            const flash = document.createElement('div');
            flash.style.cssText = 'position:fixed;top:20px;right:20px;background:#10b981;color:#fff;padding:12px 20px;border-radius:8px;font-weight:600;z-index:9999;';
            flash.textContent = 'Content saved successfully!';
            document.body.appendChild(flash);
            setTimeout(() => flash.remove(), 2000);
        }
    });
}

function escapeHtml(str) {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
function escapeAttr(str) {
    return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
</script>
@endpush
