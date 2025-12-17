@php
    $interstitial = $interstitial ?? null;
    $currentContentType = old('content_type', $interstitial?->content_type?->value ?? 'database');
    $currentAudienceType = old('audience_type', $interstitial?->audience_type?->value ?? 'all');
    $currentFrequency = old('frequency', $interstitial?->frequency?->value ?? 'once');
@endphp

<div>
    <div>
        <label for="name">Name *</label>
        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', $interstitial?->name) }}"
            required
        >
        <small>Internal identifier (must be unique)</small>
        @error('name')
            <small>{{ $message }}</small>
        @enderror
    </div>

    <div>
        <label for="title">Title *</label>
        <input
            type="text"
            id="title"
            name="title"
            value="{{ old('title', $interstitial?->title) }}"
            required
        >
        <small>Display title shown to users</small>
        @error('title')
            <small>{{ $message }}</small>
        @enderror
    </div>
</div>

<div>
    <div>
        <label for="type">Type *</label>
        <select id="type" name="type" required>
            @foreach($interstitialTypes as $type)
                <option value="{{ $type->value }}" {{ old('type', $interstitial?->type?->value) === $type->value ? 'selected' : '' }}>
                    {{ $type->label() }}
                </option>
            @endforeach
        </select>
        @error('type')
            <small>{{ $message }}</small>
        @enderror
    </div>

    <div>
        <label for="content_type">Content Type *</label>
        <select id="content_type" name="content_type" required>
            @foreach($contentTypes as $contentType)
                <option value="{{ $contentType->value }}" {{ old('content_type', $interstitial?->content_type?->value) === $contentType->value ? 'selected' : '' }}>
                    {{ $contentType->label() }}
                </option>
            @endforeach
        </select>
        @error('content_type')
            <small>{{ $message }}</small>
        @enderror
    </div>
</div>

<div id="blade-view-group" data-visible="{{ $currentContentType === 'blade_view' ? 'true' : 'false' }}">
    <label for="blade_view">Blade View</label>
    <input
        type="text"
        id="blade_view"
        name="blade_view"
        value="{{ old('blade_view', $interstitial?->blade_view) }}"
        placeholder="e.g., interstitials.welcome"
    >
    <small>The Blade view to render</small>
</div>

<div id="content-group" data-visible="{{ $currentContentType !== 'blade_view' ? 'true' : 'false' }}">
    <label for="content">Content</label>
    <div id="editor-container"></div>
    <textarea
        id="content"
        name="content"
        hidden
    >{{ old('content', $interstitial?->content) }}</textarea>
    <small>HTML content to display</small>
</div>

<hr>

<h3>Trigger Settings (Optional)</h3>

<div>
    <label for="trigger_event">Trigger Event</label>
    <input
        type="text"
        id="trigger_event"
        name="trigger_event"
        value="{{ old('trigger_event', $interstitial?->trigger_event) }}"
        placeholder="e.g., Illuminate\Auth\Events\Login"
    >
    <small>Laravel event class that triggers this interstitial</small>
</div>

<div>
    <label for="trigger_routes">Trigger Routes</label>
    <input
        type="text"
        id="trigger_routes_input"
        placeholder="dashboard, profile/*, admin/*"
    >
    <input type="hidden" id="trigger_routes" name="trigger_routes" value="{{ old('trigger_routes', json_encode($interstitial?->trigger_routes ?? [])) }}">
    <small>Comma-separated route patterns (supports wildcards)</small>
</div>

<div>
    <div>
        <label for="trigger_schedule_start">Schedule Start</label>
        <input
            type="datetime-local"
            id="trigger_schedule_start"
            name="trigger_schedule_start"
            value="{{ old('trigger_schedule_start', $interstitial?->trigger_schedule_start?->format('Y-m-d\TH:i')) }}"
        >
    </div>

    <div>
        <label for="trigger_schedule_end">Schedule End</label>
        <input
            type="datetime-local"
            id="trigger_schedule_end"
            name="trigger_schedule_end"
            value="{{ old('trigger_schedule_end', $interstitial?->trigger_schedule_end?->format('Y-m-d\TH:i')) }}"
        >
    </div>
</div>

<hr>

<h3>Audience Settings</h3>

<div>
    <div>
        <label for="audience_type">Audience *</label>
        <select id="audience_type" name="audience_type" required>
            @foreach($audienceTypes as $audienceType)
                <option value="{{ $audienceType->value }}" {{ old('audience_type', $interstitial?->audience_type?->value ?? 'all') === $audienceType->value ? 'selected' : '' }}>
                    {{ $audienceType->label() }}
                </option>
            @endforeach
        </select>
    </div>

    <div id="roles-group" data-visible="{{ $currentAudienceType === 'roles' ? 'true' : 'false' }}">
        <label for="audience_roles">Roles</label>
        <input
            type="text"
            id="audience_roles_input"
            placeholder="admin, editor, manager"
        >
        <input type="hidden" id="audience_roles" name="audience_roles" value="{{ old('audience_roles', json_encode($interstitial?->audience_roles ?? [])) }}">
        <small>Comma-separated role names</small>
    </div>
</div>

<div id="condition-group" data-visible="{{ $currentAudienceType === 'custom' ? 'true' : 'false' }}">
    <label for="audience_condition">Custom Condition Class</label>
    <input
        type="text"
        id="audience_condition"
        name="audience_condition"
        value="{{ old('audience_condition', $interstitial?->audience_condition) }}"
        placeholder="App\Conditions\HasCompletedProfile"
    >
    <small>Class implementing AudienceCondition contract</small>
</div>

<hr>

<h3>Frequency Settings</h3>

<div>
    <div>
        <label for="frequency">Frequency *</label>
        <select id="frequency" name="frequency" required>
            @foreach($frequencies as $frequency)
                <option value="{{ $frequency->value }}" {{ old('frequency', $interstitial?->frequency?->value ?? 'once') === $frequency->value ? 'selected' : '' }}>
                    {{ $frequency->label() }}
                </option>
            @endforeach
        </select>
    </div>

    <div id="frequency-days-group" data-visible="{{ $currentFrequency === 'every_x_days' ? 'true' : 'false' }}">
        <label for="frequency_days">Days Between Shows</label>
        <input
            type="number"
            id="frequency_days"
            name="frequency_days"
            value="{{ old('frequency_days', $interstitial?->frequency_days ?? 7) }}"
            min="1"
        >
    </div>
</div>

<hr>

<h3>Display Settings</h3>

<div>
    <div>
        <label for="priority">Priority</label>
        <input
            type="number"
            id="priority"
            name="priority"
            value="{{ old('priority', $interstitial?->priority ?? 0) }}"
        >
        <small>Higher priority shows first</small>
    </div>

    <div>
        <label for="queue_behavior">Queue Behavior</label>
        <select id="queue_behavior" name="queue_behavior">
            @foreach($queueBehaviors as $queueBehavior)
                <option value="{{ $queueBehavior->value }}" {{ old('queue_behavior', $interstitial?->queue_behavior?->value ?? 'inherit') === $queueBehavior->value ? 'selected' : '' }}>
                    {{ $queueBehavior->label() }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div>
    <label for="inline_slot">Inline Slot</label>
    <input
        type="text"
        id="inline_slot"
        name="inline_slot"
        value="{{ old('inline_slot', $interstitial?->inline_slot) }}"
        placeholder="sidebar-promo"
    >
    <small>Named slot for inline interstitials</small>
</div>

<div>
    <div>
        <label>
            <input type="hidden" name="allow_dismiss" value="0">
            <input
                type="checkbox"
                name="allow_dismiss"
                value="1"
                {{ old('allow_dismiss', $interstitial?->allow_dismiss ?? true) ? 'checked' : '' }}
            >
            Allow Dismiss
        </label>
        <small>Users can close without completing</small>
    </div>

    <div>
        <label>
            <input type="hidden" name="allow_dont_show_again" value="0">
            <input
                type="checkbox"
                name="allow_dont_show_again"
                value="1"
                {{ old('allow_dont_show_again', $interstitial?->allow_dont_show_again ?? false) ? 'checked' : '' }}
            >
            Allow "Don't Show Again"
        </label>
        <small>Users can permanently hide</small>
    </div>
</div>

<div>
    <label for="redirect_after">Redirect After</label>
    <input
        type="text"
        id="redirect_after"
        name="redirect_after"
        value="{{ old('redirect_after', $interstitial?->redirect_after) }}"
        placeholder="/dashboard"
    >
    <small>URL to redirect to after completion (leave empty to return to original page)</small>
</div>

<div>
    <label>
        <input type="hidden" name="is_active" value="0">
        <input
            type="checkbox"
            name="is_active"
            value="1"
            {{ old('is_active', $interstitial?->is_active ?? true) ? 'checked' : '' }}
        >
        Active
    </label>
    <small>Inactive interstitials won't be shown</small>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
(function() {
    // Helper function to toggle visibility using data attribute
    function initVisibility() {
        document.querySelectorAll('[data-visible]').forEach(function(el) {
            el.style.display = el.dataset.visible === 'true' ? '' : 'none';
        });
    }

    function setVisible(element, visible) {
        element.dataset.visible = visible ? 'true' : 'false';
        element.style.display = visible ? '' : 'none';
    }

    // Initialize visibility on load
    initVisibility();

    // Initialize Quill editor
    const editorContainer = document.getElementById('editor-container');
    if (editorContainer) {
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

        // Set initial content using Quill's clipboard API
        const contentTextarea = document.getElementById('content');
        if (contentTextarea && contentTextarea.value) {
            quill.clipboard.dangerouslyPasteHTML(contentTextarea.value);
        }

        // Update hidden textarea on change
        quill.on('text-change', function() {
            if (contentTextarea) {
                contentTextarea.value = quill.getSemanticHTML();
            }
        });
    }

    // Show/hide content type dependent fields
    const contentTypeSelect = document.getElementById('content_type');
    if (contentTypeSelect) {
        contentTypeSelect.addEventListener('change', function() {
            const bladeViewGroup = document.getElementById('blade-view-group');
            const contentGroup = document.getElementById('content-group');

            if (bladeViewGroup) setVisible(bladeViewGroup, this.value === 'blade_view');
            if (contentGroup) setVisible(contentGroup, this.value !== 'blade_view');
        });
    }

    // Show/hide audience dependent fields
    const audienceTypeSelect = document.getElementById('audience_type');
    if (audienceTypeSelect) {
        audienceTypeSelect.addEventListener('change', function() {
            const rolesGroup = document.getElementById('roles-group');
            const conditionGroup = document.getElementById('condition-group');

            if (rolesGroup) setVisible(rolesGroup, this.value === 'roles');
            if (conditionGroup) setVisible(conditionGroup, this.value === 'custom');
        });
    }

    // Show/hide frequency days
    const frequencySelect = document.getElementById('frequency');
    if (frequencySelect) {
        frequencySelect.addEventListener('change', function() {
            const frequencyDaysGroup = document.getElementById('frequency-days-group');
            if (frequencyDaysGroup) setVisible(frequencyDaysGroup, this.value === 'every_x_days');
        });
    }

    // Handle trigger routes input
    const triggerRoutesInput = document.getElementById('trigger_routes_input');
    const triggerRoutesHidden = document.getElementById('trigger_routes');
    if (triggerRoutesInput && triggerRoutesHidden) {
        const existingRoutes = JSON.parse(triggerRoutesHidden.value || '[]');
        triggerRoutesInput.value = existingRoutes.join(', ');

        triggerRoutesInput.addEventListener('change', function() {
            const routes = this.value.split(',').map(function(r) { return r.trim(); }).filter(function(r) { return r; });
            triggerRoutesHidden.value = JSON.stringify(routes);
        });
    }

    // Handle audience roles input
    const audienceRolesInput = document.getElementById('audience_roles_input');
    const audienceRolesHidden = document.getElementById('audience_roles');
    if (audienceRolesInput && audienceRolesHidden) {
        const existingRoles = JSON.parse(audienceRolesHidden.value || '[]');
        audienceRolesInput.value = existingRoles.join(', ');

        audienceRolesInput.addEventListener('change', function() {
            const roles = this.value.split(',').map(function(r) { return r.trim(); }).filter(function(r) { return r; });
            audienceRolesHidden.value = JSON.stringify(roles);
        });
    }
})();
</script>
@endpush
