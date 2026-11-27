@extends('admin.layouts.app')
@section('title', 'Open New Ticket')
@section('content')
@php
    $oldCcRaw = (string) old('cc', '');
    $initialCcList = $oldCcRaw !== ''
        ? array_values(array_filter(array_map('trim', preg_split('/[\s,;]+/', $oldCcRaw) ?: [])))
        : [];
@endphp

<style>
    .whmcs-ticket-combobox {
        position: relative;
        max-width: 390px;
        width: 100%;
    }
    .whmcs-ticket-combo-box {
        position: relative;
        display: flex;
        align-items: center;
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 3px;
        min-height: 32px;
    }
    .whmcs-ticket-combo-box input {
        width: 100%;
        border: 0 !important;
        box-shadow: none !important;
        padding: 5px 32px 5px 10px !important;
        font-size: 13px;
        outline: none;
        background: transparent;
    }
    .whmcs-ticket-combo-caret {
        position: absolute;
        right: 10px;
        color: #555;
        font-size: 11px;
        pointer-events: none;
    }
    .whmcs-ticket-combo-dd {
        position: absolute;
        top: calc(100% + 2px);
        left: 0;
        right: 0;
        max-height: 230px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #bbb;
        border-radius: 0 0 4px 4px;
        box-shadow: 0 6px 14px rgba(0,0,0,0.15);
        z-index: 1060;
    }
    .whmcs-ticket-combo-opt {
        padding: 7px 10px;
        font-size: 12.5px;
        cursor: pointer;
        border-bottom: 1px solid #f3f4f6;
    }
    .whmcs-ticket-combo-opt:hover,
    .whmcs-ticket-combo-opt.active {
        background: #eef4fb;
        color: #1a4d80;
    }
    .whmcs-cc-box {
        position: relative;
        max-width: 440px;
        width: 100%;
    }
    .whmcs-cc-tokens {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px;
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 3px;
        padding: 3px 6px;
        min-height: 32px;
    }
    .whmcs-cc-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #e8f0fb;
        border: 1px solid #bcd2f0;
        color: #1a4d80;
        border-radius: 3px;
        padding: 1px 6px;
        font-size: 12px;
    }
    .whmcs-cc-chip button {
        border: 0;
        background: none;
        color: #1a4d80;
        cursor: pointer;
        font-size: 13px;
        line-height: 1;
        padding: 0;
    }
    .whmcs-cc-tokens input {
        border: 0 !important;
        box-shadow: none !important;
        outline: none;
        flex: 1;
        min-width: 180px;
        padding: 2px 4px !important;
        font-size: 13px;
    }
    .whmcs-md-wrap {
        border: 1px solid #ccc;
        border-radius: 3px;
        background: #fff;
        margin-bottom: 12px;
    }
    .whmcs-md-wrap.fullscreen {
        position: fixed;
        inset: 16px;
        z-index: 2200;
        display: flex;
        flex-direction: column;
        margin: 0;
        box-shadow: 0 12px 36px rgba(0,0,0,0.35);
    }
    .whmcs-md-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 6px;
        padding: 6px 8px;
        background: #f5f5f5;
        border-bottom: 1px solid #ddd;
    }
    .whmcs-md-btn-group {
        display: inline-flex;
        align-items: center;
        border: 1px solid #ccc;
        border-radius: 3px;
        overflow: hidden;
        background: #fff;
    }
    .whmcs-md-btn-group button {
        border: 0;
        border-right: 1px solid #ddd;
        background: #fff;
        color: #333;
        padding: 4px 9px;
        font-size: 12.5px;
        line-height: 1.3;
        cursor: pointer;
    }
    .whmcs-md-btn-group button:last-child {
        border-right: 0;
    }
    .whmcs-md-btn-group button:hover {
        background: #ebebeb;
    }
    .whmcs-md-preview-btn {
        background: #337ab7 !important;
        color: #fff !important;
        border-color: #2e6da4 !important;
    }
    .whmcs-md-preview-btn:hover {
        background: #286090 !important;
    }
    .whmcs-md-textarea {
        width: 100%;
        border: 0 !important;
        box-shadow: none !important;
        padding: 10px 12px !important;
        font-size: 13px;
        line-height: 1.55;
        resize: vertical;
        min-height: 190px;
        outline: none;
    }
    .whmcs-md-wrap.fullscreen .whmcs-md-textarea,
    .whmcs-md-wrap.fullscreen .whmcs-md-preview {
        flex: 1;
        min-height: 0;
    }
    .whmcs-md-preview {
        padding: 12px 14px;
        min-height: 190px;
        background: #fcfcfd;
        font-size: 13px;
        line-height: 1.6;
        overflow-y: auto;
    }
    .whmcs-inline-modal {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.42);
        z-index: 2300;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .whmcs-inline-modal-card {
        background: #fff;
        border-radius: 4px;
        width: 100%;
        max-width: 440px;
        box-shadow: 0 10px 28px rgba(0,0,0,0.22);
        overflow: hidden;
    }
    [x-cloak] { display: none !important; }
</style>

<h1>{{ 'Open New Ticket' }}</h1>

@if($errors->any())
<div class="infobox" style="border-left:4px solid #c43c35;background:#f2dede;color:#a94442;margin-bottom:15px;">
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div x-data="whmcsOpenTicket()">
    <form method="POST" action="{{ route('admin.tickets.store') }}" enctype="multipart/form-data">
        @csrf

        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td style="width:140px;" class="fieldlabel"><label for="t-client-search">{{ 'Client' }}</label></td>
                <td class="fieldarea" colspan="3">
                    <input type="hidden" name="client" :value="selectedClientId || ''">
                    <div class="whmcs-ticket-combobox" @click.outside="clientDropdownOpen = false">
                        <div class="whmcs-ticket-combo-box" @click="openClientDropdown()">
                            <input type="text"
                                   id="t-client-search"
                                   x-model="clientQuery"
                                   @focus="openClientDropdown()"
                                   @input="onClientSearchInput()"
                                   @keydown.arrow-down.prevent="moveClientHighlight(1)"
                                   @keydown.arrow-up.prevent="moveClientHighlight(-1)"
                                   @keydown.enter.prevent="confirmClientHighlight()"
                                   @keydown.escape="clientDropdownOpen = false"
                                   placeholder="{{ 'Start Typing to Search Clients' }}"
                                   autocomplete="off">
                            <button type="button"
                                    x-show="selectedClientId"
                                    x-cloak
                                    @click.stop="clearClient()"
                                    style="position:absolute;right:25px;border:0;background:none;color:#888;cursor:pointer;font-size:15px;line-height:1;"
                                    title="{{ 'Clear selected client' }}">&times;</button>
                            <span class="whmcs-ticket-combo-caret">&#9662;</span>
                        </div>
                        <div class="whmcs-ticket-combo-dd" x-show="clientDropdownOpen" x-cloak>
                            <template x-for="(c, idx) in filteredClients" :key="c.id">
                                <div class="whmcs-ticket-combo-opt"
                                     :class="{ 'active': idx === clientHighlight }"
                                     @mouseenter="clientHighlight = idx"
                                     @mousedown.prevent="selectClient(c)">
                                    <strong x-text="c.label"></strong>
                                    <span style="color:#666;margin-left:5px;" x-text="c.email ? '(' + c.email + ')' : ''"></span>
                                </div>
                            </template>
                            <div x-show="filteredClients.length === 0" style="padding:8px 10px;color:#777;font-size:12px;">
                                {{ 'No matching clients found.' }}
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="fieldlabel"><label for="t-name">{{ 'Name' }}</label></td>
                <td class="fieldarea" colspan="3">
                    <input type="text"
                           id="t-name"
                           name="name"
                           x-model="ticketName"
                           value="{{ old('name', $client ? trim($client->first_name . ' ' . $client->last_name) : '') }}"
                           :readonly="Boolean(selectedClientId)"
                           :style="selectedClientId ? 'background:#f5f5f5;' : ''"
                           class="form-control input-250">
                </td>
            </tr>

            <tr>
                <td class="fieldlabel"><label for="t-email">{{ 'Email Address' }}</label></td>
                <td class="fieldarea" colspan="3">
                    <input type="email"
                           id="t-email"
                           name="email"
                           x-model="ticketEmail"
                           value="{{ old('email', $client?->email ?? '') }}"
                           :readonly="Boolean(selectedClientId)"
                           :style="selectedClientId ? 'background:#f5f5f5;' : ''"
                           class="form-control input-300">
                    <input type="hidden" name="send_email" value="0">
                    <label style="display:inline-flex;align-items:center;gap:5px;font-weight:400;margin:0 0 0 8px;cursor:pointer;vertical-align:middle;">
                        <input type="checkbox" name="send_email" value="1" @checked((bool) old('send_email', true))>
                        <span>{{ 'Send Email' }}</span>
                    </label>
                </td>
            </tr>

            <tr>
                <td class="fieldlabel"><label for="t-cc-input">{{ 'CC Recipients' }}</label></td>
                <td class="fieldarea" colspan="3">
                    <input type="hidden" name="cc" :value="serializedCc()">
                    <div class="whmcs-cc-box" @click.outside="ccDropdownOpen = false">
                        <div class="whmcs-cc-tokens" @click="$refs.ccInput.focus(); ccDropdownOpen = true">
                            <template x-for="(addr, idx) in ccList" :key="addr">
                                <span class="whmcs-cc-chip">
                                    <span x-text="addr"></span>
                                    <button type="button" @click.stop="removeCc(idx)" aria-label="{{ 'Remove CC' }}">&times;</button>
                                </span>
                            </template>
                            <input type="text"
                                   id="t-cc-input"
                                   x-ref="ccInput"
                                   x-model="ccInput"
                                   @focus="ccDropdownOpen = true"
                                   @keydown.enter.prevent="commitTypedCc()"
                                   @keydown.comma.prevent="commitTypedCc()"
                                   @keydown.backspace="if (!ccInput && ccList.length) ccList.pop()"
                                   :placeholder="ccList.length === 0 ? 'Start Typing to Add or Select Recipient' : ''"
                                   autocomplete="off">
                        </div>

                        <div class="whmcs-ticket-combo-dd" x-show="ccDropdownOpen && (filteredCcSuggestions().length > 0 || canAddTypedCc())" x-cloak>
                            <template x-for="ct in filteredCcSuggestions()" :key="ct.email">
                                <div class="whmcs-ticket-combo-opt" @mousedown.prevent="addCcAddress(ct.email)">
                                    <strong x-text="ct.name || ct.email"></strong>
                                    <span style="color:#666;margin-left:5px;" x-text="'<' + ct.email + '>'"></span>
                                </div>
                            </template>
                            <div class="whmcs-ticket-combo-opt"
                                 x-show="canAddTypedCc()"
                                 @mousedown.prevent="commitTypedCc()">
                                <i class="fas fa-plus-circle" style="color:#337ab7;margin-right:4px;"></i>
                                <span>{{ 'Add' }} </span><strong x-text="ccInput.trim()"></strong>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="fieldlabel"><label for="t-subject">{{ 'Subject' }}</label></td>
                <td class="fieldarea" colspan="3">
                    <input type="text" id="t-subject" name="subject" value="{{ old('subject') }}" required maxlength="255" class="form-control">
                </td>
            </tr>

            <tr>
                <td style="width:140px;" class="fieldlabel"><label for="t-dept">{{ 'Department' }}</label></td>
                <td width="40%" class="fieldarea">
                    <select id="t-dept" name="department_id" required class="form-control select-inline">
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" @selected(old('department_id') == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td style="width:120px;" class="fieldlabel"><label for="t-prio">{{ 'Priority' }}</label></td>
                <td class="fieldarea">
                    <select id="t-prio" name="priority" class="form-control select-inline">
                        @foreach(['High', 'Medium', 'Low'] as $p)
                            <option value="{{ $p }}" @selected(old('priority', 'Medium') === $p)>{{ $p }}</option>
                        @endforeach
                    </select>
                </td>
            </tr>
        </table>

        {{-- Related Services Table --}}
        <div class="tablebg" style="margin:8px 0 6px;">
            <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                <thead>
                    <tr>
                        <th style="width:30px;"></th>
                        <th>{{ 'Product/Service' }}</th>
                        <th>{{ 'Amount' }}</th>
                        <th>{{ 'Billing Cycle' }}</th>
                        <th>{{ 'Signup Date' }}</th>
                        <th>{{ 'Next Due Date' }}</th>
                        <th>{{ 'Status' }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr x-show="!selectedClientId && !servicesLoading">
                        <td colspan="7" style="text-align:left;padding:6px 10px;color:#444;">
                            {{ 'Please select a client to view the related services' }}
                        </td>
                    </tr>
                    <tr x-show="servicesLoading" x-cloak>
                        <td colspan="7" style="text-align:left;padding:6px 10px;color:#666;">
                            <i class="fas fa-spinner fa-spin"></i> {{ 'Loading related services...' }}
                        </td>
                    </tr>
                    <tr x-show="selectedClientId && !servicesLoading && services.length === 0" x-cloak>
                        <td colspan="7" style="text-align:left;padding:6px 10px;color:#666;">
                            {{ 'No related services found for this client' }}
                        </td>
                    </tr>
                    <tr x-show="selectedClientId && !servicesLoading && services.length > 0" x-cloak>
                        <td style="text-align:center;">
                            <input type="radio" name="related_service" value="" x-model="relatedService">
                        </td>
                        <td colspan="6"><strong>{{ 'None' }}</strong></td>
                    </tr>
                    <template x-for="svc in services" :key="svc.id">
                        <tr x-show="selectedClientId && !servicesLoading">
                            <td style="text-align:center;">
                                <input type="radio" name="related_service" :value="String(svc.id)" x-model="relatedService">
                            </td>
                            <td>
                                <strong x-text="svc.product_title"></strong>
                                <span x-show="svc.domain" style="color:#555;" x-text="' - ' + svc.domain"></span>
                            </td>
                            <td style="text-align:center;" x-text="svc.amount"></td>
                            <td style="text-align:center;" x-text="svc.billing_cycle"></td>
                            <td style="text-align:center;" x-text="svc.signup_date"></td>
                            <td style="text-align:center;" x-text="svc.next_due_date"></td>
                            <td style="text-align:center;">
                                <span class="whmcs-status" :class="'badge-' + svc.status_slug" x-text="svc.status"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div style="margin:4px 0 12px;">
            <button type="button"
                    class="btn btn-default btn-xs"
                    :disabled="!selectedClientId || showingAllServices || totalServices <= services.length"
                    @click="loadClientContext(selectedClientId, true)">
                <i class="fas fa-bars" style="font-size:10px;"></i> {{ 'View All Services' }}
            </button>
        </div>

        {{-- WHMCS Markdown Editor --}}
        <div class="whmcs-md-wrap" :class="{ 'fullscreen': fullscreenEditor }">
            <div class="whmcs-md-toolbar">
                <div style="display:flex;align-items:center;flex-wrap:wrap;gap:6px;">
                    <div class="whmcs-md-btn-group">
                        <button type="button" @click="wrapSelection('**', '**', 'bold text')" title="{{ 'Bold' }}"><strong>B</strong></button>
                        <button type="button" @click="wrapSelection('*', '*', 'italic text')" title="{{ 'Italic' }}"><em style="font-family:Georgia,serif;">I</em></button>
                        <button type="button" @click="prefixLine('### ', 'Heading')" title="{{ 'Heading' }}"><strong style="font-family:Georgia,serif;">H</strong></button>
                    </div>
                    <div class="whmcs-md-btn-group">
                        <button type="button" @click="openLinkDialog()" title="{{ 'Insert Link' }}"><i class="fas fa-link"></i></button>
                        <button type="button" @click="prefixLine('- ', 'List item')" title="{{ 'Unordered List' }}"><i class="fas fa-list-ul"></i></button>
                        <button type="button" @click="prefixLine('1. ', 'List item')" title="{{ 'Ordered List' }}"><i class="fas fa-list-ol"></i></button>
                        <button type="button" @click="wrapSelection('```\n', '\n```', 'code')" title="{{ 'Code' }}"><i class="fas fa-terminal" style="font-size:11px;"></i></button>
                        <button type="button" @click="prefixLine('> ', 'Quoted text')" title="{{ 'Quote' }}"><i class="fas fa-comment"></i></button>
                    </div>
                    <div class="whmcs-md-btn-group">
                        <button type="button" class="whmcs-md-preview-btn" @click="togglePreview()">
                            <i class="fas fa-search"></i> <span x-text="previewMode ? 'Edit' : 'Preview'">{{ 'Preview' }}</span>
                        </button>
                        <button type="button" @click="helpModalOpen = true" title="{{ 'Markdown Help' }}"><i class="fas fa-question-circle"></i></button>
                    </div>
                </div>
                <div>
                    <button type="button"
                            @click="fullscreenEditor = !fullscreenEditor"
                            style="border:0;background:none;color:#666;cursor:pointer;padding:4px 6px;"
                            title="{{ 'Toggle Fullscreen' }}">
                        <i class="fas" :class="fullscreenEditor ? 'fa-compress' : 'fa-expand'"></i>
                    </button>
                </div>
            </div>

            <textarea id="t-message"
                      x-ref="msgArea"
                      name="message"
                      x-model="messageBody"
                      x-show="!previewMode"
                      rows="9"
                      required
                      class="whmcs-md-textarea">{{ old('message') }}</textarea>

            <div x-ref="previewPane" x-show="previewMode" x-cloak class="whmcs-md-preview"></div>
        </div>

        {{-- WHMCS Predefined Replies & Knowledgebase Link Bar --}}
        <div style="margin:0 0 12px;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;background:#f8f9fa;border:1px solid #e2e7e9;border-radius:3px;padding:6px 10px;">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                @if(isset($predefined) && $predefined->isNotEmpty())
                    <select class="form-control select-inline" style="min-width:240px;height:30px;padding:3px 8px;font-size:12.5px;" @change="insertPredefined($event)">
                        <option value="">{{ 'Insert Predefined Reply...' }}</option>
                        @foreach($predefined as $category)
                            <optgroup label="{{ $category->name }}">
                                @foreach($category->replies as $saved)
                                    <option value="{{ $saved->id }}" data-reply="{{ $saved->reply }}">{{ $saved->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                @else
                    <a href="{{ route('admin.config.predefined-replies') }}" class="btn btn-default btn-xs">
                        <i class="fas fa-pencil-alt"></i> {{ 'Insert Predefined Reply' }}
                    </a>
                @endif

                @if(isset($kbArticles) && $kbArticles->isNotEmpty())
                    <select class="form-control select-inline" style="min-width:240px;height:30px;padding:3px 8px;font-size:12.5px;" @change="insertKbArticle($event)">
                        <option value="">{{ 'Insert Knowledgebase Link...' }}</option>
                        @foreach($kbArticles as $art)
                            <option value="{{ $art->id }}" data-title="{{ $art->title }}" data-url="{{ url('/client/kb/article/' . $art->id) }}">{{ $art->title }}</option>
                        @endforeach
                    </select>
                @else
                    <a href="{{ route('admin.config.knowledge-base') }}" class="btn btn-default btn-xs">
                        <i class="fas fa-book"></i> {{ 'Insert Knowledgebase Link' }}
                    </a>
                @endif
            </div>
        </div>

        {{-- Attachments --}}
        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td style="width:140px;" class="fieldlabel">{{ 'Attachments' }}</td>
                <td class="fieldarea">
                    <template x-for="(slot, idx) in attachmentSlots" :key="slot">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">
                            <input type="file" name="attachments[]" class="form-control" style="max-width:380px;padding:3px 6px;height:auto;">
                            <template x-if="idx === 0 && attachmentSlots.length < 5">
                                <button type="button" @click="addAttachmentSlot()" class="btn btn-default btn-xs">
                                    <i class="fas fa-plus"></i> {{ 'Add More' }}
                                </button>
                            </template>
                            <template x-if="idx > 0">
                                <button type="button" @click="removeAttachmentSlot(idx)" class="btn btn-default btn-xs" style="color:#d9534f;">
                                    &times; {{ 'Remove' }}
                                </button>
                            </template>
                        </div>
                    </template>
                    <div style="font-size:11.5px;color:#777;margin-top:2px;">
                        {{ 'Allowed File Extensions: .jpg, .gif, .jpeg, .png, .txt, .pdf, .csv, .doc, .docx, .zip (Max 10MB)' }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="btn-container" style="margin-top:14px;text-align:center;">
            <button type="submit" class="btn btn-primary">{{ 'Open Ticket' }}</button>
        </div>
    </form>

    {{-- Insert Link Modal --}}
    <div class="whmcs-inline-modal" x-show="linkModalOpen" x-cloak @keydown.escape.window="linkModalOpen = false" @click.self="linkModalOpen = false">
        <div class="whmcs-inline-modal-card">
            <div style="background:#1a4d80;color:#fff;padding:10px 14px;font-weight:600;display:flex;justify-content:space-between;align-items:center;">
                <span>{{ 'Insert Hyperlink' }}</span>
                <button type="button" @click="linkModalOpen = false" style="background:none;border:0;color:#fff;font-size:18px;cursor:pointer;">&times;</button>
            </div>
            <div style="padding:14px;">
                <div style="margin-bottom:10px;">
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">{{ 'Link Text' }}</label>
                    <input type="text" x-model="linkText" class="form-control" placeholder="{{ 'Example Link' }}">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">{{ 'URL' }}</label>
                    <input type="url" x-model="linkUrl" class="form-control" placeholder="https://">
                </div>
            </div>
            <div style="padding:10px 14px;background:#f8fafc;border-top:1px solid #e5e7eb;text-align:right;">
                <button type="button" class="btn btn-default btn-sm" @click="linkModalOpen = false">{{ 'Cancel' }}</button>
                <button type="button" class="btn btn-primary btn-sm" @click="confirmInsertLink()">{{ 'Insert' }}</button>
            </div>
        </div>
    </div>

    {{-- Markdown Help Modal --}}
    <div class="whmcs-inline-modal" x-show="helpModalOpen" x-cloak @keydown.escape.window="helpModalOpen = false" @click.self="helpModalOpen = false">
        <div class="whmcs-inline-modal-card">
            <div style="background:#1a4d80;color:#fff;padding:10px 14px;font-weight:600;display:flex;justify-content:space-between;align-items:center;">
                <span>{{ 'Markdown Formatting Guide' }}</span>
                <button type="button" @click="helpModalOpen = false" style="background:none;border:0;color:#fff;font-size:18px;cursor:pointer;">&times;</button>
            </div>
            <div style="padding:14px;font-size:12.5px;line-height:1.7;">
                <div><code>**bold**</code> &rarr; <strong>bold</strong></div>
                <div><code>*italic*</code> &rarr; <em>italic</em></div>
                <div><code>### Heading</code> &rarr; Section heading</div>
                <div><code>[Text](https://example.com)</code> &rarr; Hyperlink</div>
                <div><code>- item</code> / <code>1. item</code> &rarr; Bullet or numbered list</div>
                <div><code>&gt; quote</code> &rarr; Blockquote</div>
                <div><code>`code`</code> &rarr; Inline code</div>
            </div>
            <div style="padding:10px 14px;background:#f8fafc;border-top:1px solid #e5e7eb;text-align:right;">
                <button type="button" class="btn btn-default btn-sm" @click="helpModalOpen = false">{{ 'Close' }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function whmcsOpenTicket() {
    const initialClients = @json($clientOptions);
    const preselectedClient = @json($selectedClientOption);
    const oldClientVal = @json(old('client'));
    const lookupUrl = @json(route('admin.tickets.client-lookup'));
    const contextBaseUrl = @json(url('admin/tickets/client-context'));

    let initClient = preselectedClient;
    if (!initClient && oldClientVal) {
        initClient = initialClients.find(c => String(c.id) === String(oldClientVal) || c.email === oldClientVal) || null;
    }

    return {
        allClients: initialClients,
        filteredClients: initialClients,
        selectedClientId: initClient ? String(initClient.id) : (oldClientVal ? String(oldClientVal) : ''),
        clientQuery: initClient ? (initClient.label + (initClient.email ? ' (' + initClient.email + ')' : '')) : (oldClientVal ? String(oldClientVal) : ''),
        clientDropdownOpen: false,
        clientHighlight: 0,
        searchTimer: null,

        ticketName: @json(old('name', $client ? trim($client->first_name . ' ' . $client->last_name) : '')),
        ticketEmail: @json(old('email', $client?->email ?? '')),

        ccList: @json($initialCcList),
        ccInput: '',
        ccDropdownOpen: false,
        contacts: @json($serializedContacts ?? []),

        services: @json($serializedServices ?? []),
        totalServices: Number(@json($totalServicesCount ?? 0)),
        showingAllServices: false,
        servicesLoading: false,
        relatedService: String(@json(old('related_service', ''))),

        messageBody: @json(old('message', '')),
        previewMode: false,
        fullscreenEditor: false,
        linkModalOpen: false,
        linkText: '',
        linkUrl: 'https://',
        helpModalOpen: false,

        attachmentSlots: [1],
        nextSlotId: 2,

        openClientDropdown() {
            this.clientDropdownOpen = true;
            this.filterLocalClients();
        },

        filterLocalClients() {
            const q = (this.clientQuery || '').trim().toLowerCase();
            if (!q || (this.selectedClientId && this.clientQuery.includes('#' + this.selectedClientId))) {
                this.filteredClients = this.allClients.slice(0, 50);
            } else {
                this.filteredClients = this.allClients.filter(c =>
                    (c.label && c.label.toLowerCase().includes(q)) ||
                    (c.email && c.email.toLowerCase().includes(q))
                ).slice(0, 50);
            }
            this.clientHighlight = 0;
        },

        onClientSearchInput() {
            this.selectedClientId = '';
            this.services = [];
            this.totalServices = 0;
            this.contacts = [];
            this.clientDropdownOpen = true;
            this.filterLocalClients();

            clearTimeout(this.searchTimer);
            const q = (this.clientQuery || '').trim();
            if (q.length < 2) {
                return;
            }
            this.searchTimer = setTimeout(() => {
                fetch(lookupUrl + '?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (data && Array.isArray(data.clients)) {
                        this.filteredClients = data.clients;
                        this.clientHighlight = 0;
                    }
                })
                .catch(() => {});
            }, 220);
        },

        moveClientHighlight(delta) {
            if (!this.filteredClients.length) return;
            this.clientHighlight = Math.max(0, Math.min(this.filteredClients.length - 1, this.clientHighlight + delta));
        },

        confirmClientHighlight() {
            if (this.clientDropdownOpen && this.filteredClients[this.clientHighlight]) {
                this.selectClient(this.filteredClients[this.clientHighlight]);
            }
        },

        selectClient(c) {
            this.selectedClientId = String(c.id);
            this.clientQuery = c.label + (c.email ? ' (' + c.email + ')' : '');
            this.ticketName = c.name || '';
            this.ticketEmail = c.email || '';
            this.relatedService = '';
            this.showingAllServices = false;
            this.clientDropdownOpen = false;
            this.loadClientContext(c.id, false);
        },

        clearClient() {
            this.selectedClientId = '';
            this.clientQuery = '';
            this.ticketName = '';
            this.ticketEmail = '';
            this.services = [];
            this.totalServices = 0;
            this.contacts = [];
            this.relatedService = '';
            this.showingAllServices = false;
        },

        loadClientContext(clientId, all) {
            if (!clientId) return;
            this.servicesLoading = true;
            const url = contextBaseUrl + '/' + encodeURIComponent(clientId) + (all ? '?all=1' : '');
            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (data) {
                    this.services = Array.isArray(data.services) ? data.services : [];
                    this.totalServices = Number(data.total_services || this.services.length);
                    this.contacts = Array.isArray(data.contacts) ? data.contacts : [];
                    if (all) {
                        this.showingAllServices = true;
                    }
                }
            })
            .catch(() => {})
            .finally(() => {
                this.servicesLoading = false;
            });
        },

        filteredCcSuggestions() {
            const q = (this.ccInput || '').trim().toLowerCase();
            return this.contacts.filter(ct => {
                if (!ct.email || this.ccList.includes(ct.email)) return false;
                if (!q) return true;
                return ct.email.toLowerCase().includes(q) || (ct.name && ct.name.toLowerCase().includes(q));
            });
        },

        canAddTypedCc() {
            const v = (this.ccInput || '').trim();
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) && !this.ccList.includes(v);
        },

        addCcAddress(addr) {
            const clean = (addr || '').trim().replace(/,+$/, '');
            if (clean && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(clean) && !this.ccList.includes(clean)) {
                this.ccList.push(clean);
            }
            this.ccInput = '';
            this.ccDropdownOpen = false;
        },

        commitTypedCc() {
            if (this.canAddTypedCc()) {
                this.addCcAddress(this.ccInput);
            }
        },

        removeCc(idx) {
            this.ccList.splice(idx, 1);
        },

        serializedCc() {
            const items = [...this.ccList];
            const pending = (this.ccInput || '').trim();
            if (pending && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(pending) && !items.includes(pending)) {
                items.push(pending);
            }
            return items.join(', ');
        },

        wrapSelection(prefix, suffix, placeholder) {
            const area = this.$refs.msgArea;
            if (!area) return;
            this.previewMode = false;
            const start = area.selectionStart || 0;
            const end = area.selectionEnd || 0;
            const selected = this.messageBody.slice(start, end) || placeholder;
            this.messageBody = this.messageBody.slice(0, start) + prefix + selected + suffix + this.messageBody.slice(end);
            this.$nextTick(() => {
                area.focus();
                area.setSelectionRange(start + prefix.length, start + prefix.length + selected.length);
            });
        },

        prefixLine(prefix, placeholder) {
            const area = this.$refs.msgArea;
            if (!area) return;
            this.previewMode = false;
            const start = area.selectionStart || 0;
            const end = area.selectionEnd || 0;
            const selected = this.messageBody.slice(start, end) || placeholder;
            const needsLeadingNewline = start > 0 && this.messageBody[start - 1] !== '\n';
            const insert = (needsLeadingNewline ? '\n' : '') + prefix + selected;
            this.messageBody = this.messageBody.slice(0, start) + insert + this.messageBody.slice(end);
            this.$nextTick(() => area.focus());
        },

        openLinkDialog() {
            const area = this.$refs.msgArea;
            const start = area ? (area.selectionStart || 0) : 0;
            const end = area ? (area.selectionEnd || 0) : 0;
            this.linkText = this.messageBody.slice(start, end) || '';
            this.linkUrl = 'https://';
            this.linkModalOpen = true;
        },

        confirmInsertLink() {
            const url = (this.linkUrl || '').trim();
            if (!url) {
                this.linkModalOpen = false;
                return;
            }
            const label = (this.linkText || '').trim() || url;
            this.wrapSelection('[' + label + '](', ')', url);
            this.linkModalOpen = false;
        },

        togglePreview() {
            this.previewMode = !this.previewMode;
            if (this.previewMode) {
                this.renderSafePreview();
            }
        },

        renderSafePreview() {
            const pane = this.$refs.previewPane;
            if (!pane) return;
            pane.replaceChildren();
            const text = (this.messageBody || '').trim();
            if (!text) {
                const empty = document.createElement('em');
                empty.style.color = '#888';
                empty.textContent = 'Nothing to preview.';
                pane.appendChild(empty);
                return;
            }
            const lines = text.split(/\r?\n/);
            lines.forEach(line => {
                if (line.startsWith('### ')) {
                    const h = document.createElement('h4');
                    h.style.margin = '8px 0 4px';
                    h.textContent = line.slice(4);
                    pane.appendChild(h);
                } else if (line.startsWith('> ')) {
                    const bq = document.createElement('blockquote');
                    bq.style.borderLeft = '3px solid #cbd5e1';
                    bq.style.paddingLeft = '10px';
                    bq.style.margin = '4px 0';
                    bq.style.color = '#555';
                    bq.textContent = line.slice(2);
                    pane.appendChild(bq);
                } else if (line.startsWith('- ') || /^\d+\.\s/.test(line)) {
                    const li = document.createElement('div');
                    li.style.paddingLeft = '14px';
                    li.textContent = '• ' + line.replace(/^(- |\d+\.\s)/, '');
                    pane.appendChild(li);
                } else {
                    const p = document.createElement('p');
                    p.style.margin = '0 0 6px';
                    p.textContent = line;
                    pane.appendChild(p);
                }
            });
        },

        insertPredefined(ev) {
            const sel = ev.target;
            if (!sel || !sel.value) return;
            const opt = sel.options[sel.selectedIndex];
            const replyText = opt ? (opt.dataset.reply || '') : '';
            if (replyText) {
                this.messageBody = (this.messageBody ? this.messageBody.replace(/\s*$/, '') + '\n\n' : '') + replyText;
                if (this.previewMode) {
                    this.renderSafePreview();
                }
            }
            sel.value = '';
        },

        insertKbArticle(ev) {
            const sel = ev.target;
            if (!sel || !sel.value) return;
            const opt = sel.options[sel.selectedIndex];
            const title = opt ? (opt.dataset.title || '') : '';
            const url = opt ? (opt.dataset.url || '') : '';
            if (title && url) {
                const mdLink = '[' + title + '](' + url + ')';
                this.messageBody = (this.messageBody ? this.messageBody.replace(/\s*$/, '') + '\n\n' : '') + mdLink;
                if (this.previewMode) {
                    this.renderSafePreview();
                }
            }
            sel.value = '';
        },

        addAttachmentSlot() {
            if (this.attachmentSlots.length < 5) {
                this.attachmentSlots.push(this.nextSlotId++);
            }
        },

        removeAttachmentSlot(idx) {
            this.attachmentSlots.splice(idx, 1);
        }
    };
}
</script>
@endpush
@endsection
