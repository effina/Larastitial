@php
    $interstitial = $interstitial ?? null;
@endphp

<div class="form-row">
    <div class="form-group">
        <label for="name" class="form-label">Name *</label>
        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', $interstitial?->name) }}"
            class="form-input"
            required
        >
        <p class="form-help">Internal identifier (must be unique)</p>
        @error('name')
            <p class="form-help" style="color: var(--danger);">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-group">
        <label for="title" class="form-label">Title *</label>
        <input
            type="text"
            id="title"
            name="title"
            value="{{ old('title', $interstitial?->title) }}"
            class="form-input"
            required
        >
        <p class="form-help">Display title shown to users</p>
        @error('title')
            <p class="form-help" style="color: var(--danger);">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label for="type" class="form-label">Type *</label>
        <select id="type" name="type" class="form-select" required>
            @foreach($interstitialTypes as $type)
                <option value="{{ $type->value }}" {{ old('type', $interstitial?->type->value) === $type->value ? 'selected' : '' }}>
                    {{ $type->label() }}
                </option>
            @endforeach
        </select>
        @error('type')
            <p class="form-help" style="color: var(--danger);">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-group">
        <label for="content_type" class="form-label">Content Type *</label>
        <select id="content_type" name="content_type" class="form-select" required>
            @foreach($contentTypes as $contentType)
                <option value="{{ $contentType->value }}" {{ old('content_type', $interstitial?->content_type->value) === $contentType->value ? 'selected' : '' }}>
                    {{ $contentType->label() }}
                </option>
            @endforeach
        </select>
        @error('content_type')
            <p class="form-help" style="color: var(--danger);">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="form-group" id="blade-view-group" style="{{ old('content_type', $interstitial?->content_type->value) === 'blade_view' ? '' : 'display: none;' }}">
    <label for="blade_view" class="form-label">Blade View</label>
    <input
        type="text"
        id="blade_view"
        name="blade_view"
        value="{{ old('blade_view', $interstitial?->blade_view) }}"
        class="form-input"
        placeholder="e.g., interstitials.welcome"
    >
    <p class="form-help">The Blade view to render</p>
</div>

<div class="form-group" id="content-group" style="{{ old('content_type', $interstitial?->content_type->value) !== 'blade_view' ? '' : 'display: none;' }}">
    <label for="content" class="form-label">Content</label>
    <div id="editor-container" style="height: 200px; border: 1px solid var(--gray-300); border-radius: 6px; margin-bottom: 0.5rem;"></div>
    <textarea
        id="content"
        name="content"
        class="form-textarea"
        style="display: none;"
    >{{ old('content', $interstitial?->content) }}</textarea>
    <p class="form-help">HTML content to display</p>
</div>

<hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--gray-200);">

<h3 style="margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">Trigger Settings</h3>

<div class="form-group">
    <label for="trigger_event" class="form-label">Trigger Event</label>
    <input
        type="text"
        id="trigger_event"
        name="trigger_event"
        value="{{ old('trigger_event', $interstitial?->trigger_event) }}"
        class="form-input"
        placeholder="e.g., Illuminate\Auth\Events\Login"
    >
    <p class="form-help">Laravel event class that triggers this interstitial</p>
</div>

<div class="form-group">
    <label for="trigger_routes" class="form-label">Trigger Routes</label>
    <input
        type="text"
        id="trigger_routes_input"
        class="form-input"
        placeholder="dashboard, profile/*, admin/*"
    >
    <input type="hidden" id="trigger_routes" name="trigger_routes" value="{{ old('trigger_routes', json_encode($interstitial?->trigger_routes ?? [])) }}">
    <p class="form-help">Comma-separated route patterns (supports wildcards)</p>
</div>

<div class="form-row">
    <div class="form-group">
        <label for="trigger_schedule_start" class="form-label">Schedule Start</label>
        <input
            type="datetime-local"
            id="trigger_schedule_start"
            name="trigger_schedule_start"
            value="{{ old('trigger_schedule_start', $interstitial?->trigger_schedule_start?->format('Y-m-d\TH:i')) }}"
            class="form-input"
        >
    </div>

    <div class="form-group">
        <label for="trigger_schedule_end" class="form-label">Schedule End</label>
        <input
            type="datetime-local"
            id="trigger_schedule_end"
            name="trigger_schedule_end"
            value="{{ old('trigger_schedule_end', $interstitial?->trigger_schedule_end?->format('Y-m-d\TH:i')) }}"
            class="form-input"
        >
    </div>
</div>

<hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--gray-200);">

<h3 style="margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">Audience Settings</h3>

<div class="form-row">
    <div class="form-group">
        <label for="audience_type" class="form-label">Audience *</label>
        <select id="audience_type" name="audience_type" class="form-select" required>
            @foreach($audienceTypes as $audienceType)
                <option value="{{ $audienceType->value }}" {{ old('audience_type', $interstitial?->audience_type->value ?? 'all') === $audienceType->value ? 'selected' : '' }}>
                    {{ $audienceType->label() }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group" id="roles-group" style="{{ old('audience_type', $interstitial?->audience_type->value) === 'roles' ? '' : 'display: none;' }}">
        <label for="audience_roles" class="form-label">Roles</label>
        <input
            type="text"
            id="audience_roles_input"
            class="form-input"
            placeholder="admin, editor, manager"
        >
        <input type="hidden" id="audience_roles" name="audience_roles" value="{{ old('audience_roles', json_encode($interstitial?->audience_roles ?? [])) }}">
        <p class="form-help">Comma-separated role names</p>
    </div>
</div>

<div class="form-group" id="condition-group" style="{{ old('audience_type', $interstitial?->audience_type->value) === 'custom' ? '' : 'display: none;' }}">
    <label for="audience_condition" class="form-label">Custom Condition Class</label>
    <input
        type="text"
        id="audience_condition"
        name="audience_condition"
        value="{{ old('audience_condition', $interstitial?->audience_condition) }}"
        class="form-input"
        placeholder="App\Conditions\HasCompletedProfile"
    >
    <p class="form-help">Class implementing AudienceCondition contract</p>
</div>

<hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--gray-200);">

<h3 style="margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">Frequency Settings</h3>

<div class="form-row">
    <div class="form-group">
        <label for="frequency" class="form-label">Frequency *</label>
        <select id="frequency" name="frequency" class="form-select" required>
            @foreach($frequencies as $frequency)
                <option value="{{ $frequency->value }}" {{ old('frequency', $interstitial?->frequency->value ?? 'once') === $frequency->value ? 'selected' : '' }}>
                    {{ $frequency->label() }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group" id="frequency-days-group" style="{{ old('frequency', $interstitial?->frequency->value) === 'every_x_days' ? '' : 'display: none;' }}">
        <label for="frequency_days" class="form-label">Days Between Shows</label>
        <input
            type="number"
            id="frequency_days"
            name="frequency_days"
            value="{{ old('frequency_days', $interstitial?->frequency_days ?? 7) }}"
            class="form-input"
            min="1"
        >
    </div>
</div>

<hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--gray-200);">

<h3 style="margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">Display Settings</h3>

<div class="form-row">
    <div class="form-group">
        <label for="priority" class="form-label">Priority</label>
        <input
            type="number"
            id="priority"
            name="priority"
            value="{{ old('priority', $interstitial?->priority ?? 0) }}"
            class="form-input"
        >
        <p class="form-help">Higher priority shows first</p>
    </div>

    <div class="form-group">
        <label for="queue_behavior" class="form-label">Queue Behavior</label>
        <select id="queue_behavior" name="queue_behavior" class="form-select">
            @foreach($queueBehaviors as $queueBehavior)
                <option value="{{ $queueBehavior->value }}" {{ old('queue_behavior', $interstitial?->queue_behavior->value ?? 'inherit') === $queueBehavior->value ? 'selected' : '' }}>
                    {{ $queueBehavior->label() }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="form-group">
    <label for="inline_slot" class="form-label">Inline Slot</label>
    <input
        type="text"
        id="inline_slot"
        name="inline_slot"
        value="{{ old('inline_slot', $interstitial?->inline_slot) }}"
        class="form-input"
        placeholder="sidebar-promo"
    >
    <p class="form-help">Named slot for inline interstitials</p>
</div>

<div class="form-row">
    <div class="form-group">
        <label class="form-checkbox">
            <input type="hidden" name="allow_dismiss" value="0">
            <input
                type="checkbox"
                name="allow_dismiss"
                value="1"
                {{ old('allow_dismiss', $interstitial?->allow_dismiss ?? true) ? 'checked' : '' }}
            >
            Allow Dismiss
        </label>
        <p class="form-help">Users can close without completing</p>
    </div>

    <div class="form-group">
        <label class="form-checkbox">
            <input type="hidden" name="allow_dont_show_again" value="0">
            <input
                type="checkbox"
                name="allow_dont_show_again"
                value="1"
                {{ old('allow_dont_show_again', $interstitial?->allow_dont_show_again ?? false) ? 'checked' : '' }}
            >
            Allow "Don't Show Again"
        </label>
        <p class="form-help">Users can permanently hide</p>
    </div>
</div>

<div class="form-group">
    <label for="redirect_after" class="form-label">Redirect After</label>
    <input
        type="text"
        id="redirect_after"
        name="redirect_after"
        value="{{ old('redirect_after', $interstitial?->redirect_after) }}"
        class="form-input"
        placeholder="/dashboard"
    >
    <p class="form-help">URL to redirect to after completion (leave empty to return to original page)</p>
</div>

<div class="form-group">
    <label class="form-checkbox">
        <input type="hidden" name="is_active" value="0">
        <input
            type="checkbox"
            name="is_active"
            value="1"
            {{ old('is_active', $interstitial?->is_active ?? true) ? 'checked' : '' }}
        >
        Active
    </label>
    <p class="form-help">Inactive interstitials won't be shown</p>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
    // Initialize Quill editor
    const quill = new Quill('#editor-container', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'header': 1 }, { 'header': 2 }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                ['link', 'image'],
                ['clean']
            ]
        }
    });

    // Set initial content using Quill's clipboard API (safer than innerHTML)
    const contentTextarea = document.getElementById('content');
    if (contentTextarea.value) {
        // Use Quill's built-in clipboard to safely paste HTML content
        quill.clipboard.dangerouslyPasteHTML(contentTextarea.value);
    }

    // Update hidden textarea on change
    quill.on('text-change', function() {
        contentTextarea.value = quill.getSemanticHTML();
    });

    // Show/hide content type dependent fields
    document.getElementById('content_type').addEventListener('change', function() {
        const bladeViewGroup = document.getElementById('blade-view-group');
        const contentGroup = document.getElementById('content-group');

        if (this.value === 'blade_view') {
            bladeViewGroup.style.display = '';
            contentGroup.style.display = 'none';
        } else {
            bladeViewGroup.style.display = 'none';
            contentGroup.style.display = '';
        }
    });

    // Show/hide audience dependent fields
    document.getElementById('audience_type').addEventListener('change', function() {
        document.getElementById('roles-group').style.display = this.value === 'roles' ? '' : 'none';
        document.getElementById('condition-group').style.display = this.value === 'custom' ? '' : 'none';
    });

    // Show/hide frequency days
    document.getElementById('frequency').addEventListener('change', function() {
        document.getElementById('frequency-days-group').style.display = this.value === 'every_x_days' ? '' : 'none';
    });

    // Handle trigger routes input
    const triggerRoutesInput = document.getElementById('trigger_routes_input');
    const triggerRoutesHidden = document.getElementById('trigger_routes');
    const existingRoutes = JSON.parse(triggerRoutesHidden.value || '[]');
    triggerRoutesInput.value = existingRoutes.join(', ');

    triggerRoutesInput.addEventListener('change', function() {
        const routes = this.value.split(',').map(r => r.trim()).filter(r => r);
        triggerRoutesHidden.value = JSON.stringify(routes);
    });

    // Handle audience roles input
    const audienceRolesInput = document.getElementById('audience_roles_input');
    const audienceRolesHidden = document.getElementById('audience_roles');
    const existingRoles = JSON.parse(audienceRolesHidden.value || '[]');
    audienceRolesInput.value = existingRoles.join(', ');

    audienceRolesInput.addEventListener('change', function() {
        const roles = this.value.split(',').map(r => r.trim()).filter(r => r);
        audienceRolesHidden.value = JSON.stringify(roles);
    });
</script>
@endpush
