<div class="image-uploader" style="display:flex; flex-direction:column; gap:8px;">
    <label style="font-size:13px; color:var(--text-muted);">Image</label>
    <div style="display:flex; gap:8px; align-items:center;">
        <input type="file" name="{{ $name ?? 'image_file' }}" accept="image/*">
        <div style="flex:1; display:flex; gap:8px; align-items:center;">
            <input type="text" name="{{ $urlName ?? 'image_url' }}" value="{{ $existing ?? old($urlName ?? 'image_url') }}" placeholder="Image URL" style="width:100%;">
        </div>
    </div>
    @if(!empty($existing))
        <div style="display:flex; gap:8px; align-items:center;">
            <img src="{{ $existing }}" alt="preview" style="height:48px; width:48px; object-fit:cover; border-radius:4px; border:1px solid var(--border);">
            <span style="color:var(--text-muted); font-size:13px;">Current</span>
        </div>
    @endif
</div>
