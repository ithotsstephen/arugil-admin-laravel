@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Add Business</h2>
        <p class="muted">Create a new business listing.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.businesses.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="card" style="max-width: 900px;">
        <h3 style="margin-bottom: 16px;">Basic Information</h3>
        
        <label>Business Name*</label>
        <input type="text" name="name" required value="{{ old('name') }}">
        
        <label>Category*</label>
        <select name="category_id" required>
            <option value="">Select Category</option>
            @foreach($categories as $category)
                <optgroup label="{{ $category->name }}">
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @foreach($category->children as $child)
                        <option value="{{ $child->id }}">— {{ $child->name }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        
        <label>Owner Name</label>
        <input type="text" name="owner_name" value="{{ old('owner_name') }}" placeholder="Enter owner's name">

        <label>Owner DP Image</label>
        <div style="background: #f8fafc; padding: 16px; border-radius: 8px; margin-bottom: 14px;">
            <div style="display: flex; gap: 16px; margin-bottom: 12px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="radio" name="owner_image_type" value="upload" checked onchange="toggleOwnerImageInput()">
                    <span>Upload Image</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="radio" name="owner_image_type" value="url" onchange="toggleOwnerImageInput()">
                    <span>Image URL</span>
                </label>
            </div>
            <div id="owner_upload_input">
                <input type="file" name="owner_image_file" accept="image/*">
            </div>
            <div id="owner_url_input" style="display: none;">
                <input type="url" name="owner_image_url" placeholder="https://example.com/owner.jpg" value="{{ old('owner_image_url') }}">
            </div>
        </div>
        
        <label>Years of Business</label>
        <input type="number" name="years_of_business" value="{{ old('years_of_business') }}" placeholder="Enter years in business" min="0" max="150">
        
        <label>Hero Image</label>
        <div style="background: #f8fafc; padding: 16px; border-radius: 8px; margin-bottom: 14px;">
            <div style="display: flex; gap: 16px; margin-bottom: 12px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="radio" name="image_type" value="upload" checked onchange="toggleImageInput('hero')">
                    <span>Upload Image</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="radio" name="image_type" value="url" onchange="toggleImageInput('hero')">
                    <span>Image URL</span>
                </label>
            </div>
            <div id="hero_upload_input">
                <input type="file" name="image_file" accept="image/*">
            </div>
            <div id="hero_url_input" style="display: none;">
                <input type="url" name="image_url" placeholder="https://example.com/image.jpg" value="{{ old('image_url') }}">
            </div>
        </div>
        
        <label>Description</label>
        <textarea name="description" rows="3">{{ old('description') }}</textarea>
        
        <label>About Business Title</label>
        <input type="text" name="about_title" value="{{ old('about_title') }}">
        
        <label>Services Offered</label>
        <div id="servicesContainer">
            <div class="service-item" style="display: flex; gap: 8px; margin-bottom: 12px; align-items: start;">
                <div style="flex: 1;">
                    <input type="text" name="services[0][title]" placeholder="Service title" style="margin-bottom: 8px;" required>
                    <textarea name="services[0][description]" rows="2" placeholder="Service description" required></textarea>
                </div>
                <button type="button" class="btn" onclick="removeService(this)" style="background: #ef4444; margin-top: 0;">✕</button>
            </div>
        </div>
        <button type="button" class="btn" onclick="addService()" style="background: #10b981; margin-top: 8px;">+ Add Service</button>
        
        <h3 style="margin: 24px 0 16px;">Special Offers</h3>
        
        <div id="offersContainer">
            <div class="offer-item" style="border: 1px solid var(--border); padding: 16px; border-radius: 8px; margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                    <h4 style="margin: 0; font-size: 14px; color: var(--text);">Offer #1</h4>
                    <button type="button" class="btn" onclick="removeOffer(this)" style="background: #ef4444; padding: 6px 12px; font-size: 12px;">✕</button>
                </div>
                <label>Offer Image</label>
                <div style="background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 14px;">
                    <div style="display: flex; gap: 16px; margin-bottom: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="offers[0][image_type]" value="upload" checked onchange="toggleOfferImageInput(0)">
                            <span>Upload</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="offers[0][image_type]" value="url" onchange="toggleOfferImageInput(0)">
                            <span>URL</span>
                        </label>
                    </div>
                    <div id="offer_0_upload">
                        <input type="file" name="offers[0][image_file]" accept="image/*" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border);">
                    </div>
                    <div id="offer_0_url" style="display: none;">
                        <input type="url" name="offers[0][image_url]" placeholder="https://example.com/offer.jpg" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border);">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label>Start Date & Time</label>
                        <input type="datetime-local" name="offers[0][start_date]" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                    </div>
                    <div>
                        <label>End Date & Time</label>
                        <input type="datetime-local" name="offers[0][end_date]" value="{{ now()->addDay()->format('Y-m-d\TH:i') }}" required>
                    </div>
                </div>
            </div>
        </div>
        <button type="button" class="btn" onclick="addOffer()" style="background: #10b981; margin-top: 8px;">+ Add Offer</button>
        
        <h3 style="margin: 24px 0 16px;">Contact Details</h3>
        
        <label>Phone</label>
        <input type="text" name="phone" value="{{ old('phone') }}">
        
        <label>WhatsApp</label>
        <input type="text" name="whatsapp" value="{{ old('whatsapp') }}">
        
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}">
        
        <label>Website</label>
        <input type="url" name="website" value="{{ old('website') }}">
        
        <h3 style="margin: 24px 0 16px;">Location</h3>
        
        <label>Address</label>
        <input type="text" name="address" value="{{ old('address') }}">
        
        <label>Latitude</label>
        <input type="text" name="latitude" value="{{ old('latitude') }}">
        
        <label>Longitude</label>
        <input type="text" name="longitude" value="{{ old('longitude') }}">
        
        <h3 style="margin: 24px 0 16px;">Social Media</h3>
        
        <label>Facebook</label>
        <input type="url" name="facebook" value="{{ old('facebook') }}">
        
        <label>Instagram</label>
        <input type="url" name="instagram" value="{{ old('instagram') }}">
        
        <label>Twitter</label>
        <input type="url" name="twitter" value="{{ old('twitter') }}">
        
        <label>LinkedIn</label>
        <input type="url" name="linkedin" value="{{ old('linkedin') }}">
        
        <h3 style="margin: 24px 0 16px;">Photo Gallery</h3>
        
        <div id="galleryContainer">
            <div class="gallery-item" style="border: 1px solid var(--border); padding: 16px; border-radius: 8px; margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                    <h4 style="margin: 0; font-size: 14px; color: var(--text);">Image #1</h4>
                    <button type="button" class="btn" onclick="removeGalleryImage(this)" style="background: #ef4444; padding: 6px 12px; font-size: 12px;">✕</button>
                </div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 8px;">
                    <div style="display: flex; gap: 16px; margin-bottom: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="gallery[0][image_type]" value="upload" checked onchange="toggleGalleryImageInput(0)">
                            <span>Upload</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="gallery[0][image_type]" value="url" onchange="toggleGalleryImageInput(0)">
                            <span>URL</span>
                        </label>
                    </div>
                    <div id="gallery_0_upload">
                        <input type="file" name="gallery[0][image_file]" accept="image/*" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border);">
                    </div>
                    <div id="gallery_0_url" style="display: none;">
                        <input type="url" name="gallery[0][image_url]" placeholder="https://example.com/image.jpg" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border);">
                    </div>
                </div>
            </div>
        </div>
        <button type="button" class="btn" onclick="addGalleryImage()" style="background: #10b981; margin-top: 8px;">+ Add Gallery Image</button>

        <h3 style="margin: 24px 0 16px;">Payments</h3>
        <p class="muted" style="margin-top: -6px; margin-bottom: 12px; font-size: 12px;">Record payments made by the business (currency: ₹ INR).</p>

        <div id="paymentsContainer">
            <div class="payment-item" style="border: 1px solid var(--border); padding: 16px; border-radius: 8px; margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                    <h4 style="margin: 0; font-size: 14px; color: var(--text);">Payment #1</h4>
                    <button type="button" class="btn" onclick="removePayment(this)" style="background: #ef4444; padding: 6px 12px; font-size: 12px;">✕</button>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label>Amount (₹)</label>
                        <input type="number" name="payments[0][amount]" step="0.01" min="0">
                    </div>
                    <div>
                        <label>Payment Date</label>
                        <input type="date" name="payments[0][paid_at]" value="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>
                <label>Transaction ID</label>
                <input type="text" name="payments[0][transaction_id]" placeholder="Optional">
                <label>Description</label>
                <textarea name="payments[0][description]" rows="2" placeholder="Payment notes (optional)"></textarea>
            </div>
        </div>
        <button type="button" class="btn" onclick="addPayment()" style="background: #10b981; margin-top: 8px;">+ Add Payment</button>
        
        <h3 style="margin: 24px 0 16px;">Status</h3>
        
        <label>Expiry Date</label>
        <input type="date" name="expiry_date" value="{{ old('expiry_date', now()->addYear()->format('Y-m-d')) }}">
        <p class="muted" style="margin-top: -10px; margin-bottom: 14px; font-size: 12px;">Default: 1 year from today</p>
        
        <label style="display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" name="is_approved" value="1" {{ old('is_approved') ? 'checked' : '' }}>
            Approve immediately
        </label>
        
        <div style="display: flex; gap: 8px; margin-top: 16px;">
            <button type="submit" class="btn btn-primary">Create Business</button>
            <a href="{{ route('admin.businesses.index') }}" class="btn">Cancel</a>
        </div>
    </div>
</form>

<style>
    label { display: block; margin-bottom: 6px; font-size: 13px; color: var(--muted); font-weight: 500; }
    input, textarea, select { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 14px; }
    h3 { font-size: 16px; color: var(--text); }
</style>

<script>
let serviceIndex = 1;
let offerIndex = 1;
let galleryIndex = 1;
let paymentIndex = 1;

function toggleImageInput(type) {
    const uploadInput = document.getElementById(type + '_upload_input');
    const urlInput = document.getElementById(type + '_url_input');
    const radio = document.querySelector('input[name="image_type"]:checked').value;
    
    if (radio === 'upload') {
        uploadInput.style.display = 'block';
        urlInput.style.display = 'none';
        uploadInput.querySelector('input').required = false;
        urlInput.querySelector('input').required = false;
    } else {
        uploadInput.style.display = 'none';
        urlInput.style.display = 'block';
        uploadInput.querySelector('input').required = false;
        urlInput.querySelector('input').required = false;
    }
}

function toggleOfferImageInput(index) {
    const uploadInput = document.getElementById('offer_' + index + '_upload');
    const urlInput = document.getElementById('offer_' + index + '_url');
    const radio = document.querySelector('input[name="offers[' + index + '][image_type]"]:checked').value;
    
    if (radio === 'upload') {
        uploadInput.style.display = 'block';
        urlInput.style.display = 'none';
    } else {
        uploadInput.style.display = 'none';
        urlInput.style.display = 'block';
    }
}

function toggleGalleryImageInput(index) {
    const uploadInput = document.getElementById('gallery_' + index + '_upload');
    const urlInput = document.getElementById('gallery_' + index + '_url');
    const radio = document.querySelector('input[name="gallery[' + index + '][image_type]"]:checked').value;
    
    if (radio === 'upload') {
        uploadInput.style.display = 'block';
        urlInput.style.display = 'none';
    } else {
        uploadInput.style.display = 'none';
        urlInput.style.display = 'block';
    }
}

function toggleOwnerImageInput() {
    const uploadInput = document.getElementById('owner_upload_input');
    const urlInput = document.getElementById('owner_url_input');
    const radio = document.querySelector('input[name="owner_image_type"]:checked').value;

    if (radio === 'upload') {
        uploadInput.style.display = 'block';
        urlInput.style.display = 'none';
    } else {
        uploadInput.style.display = 'none';
        urlInput.style.display = 'block';
    }
}

function addService() {
    const container = document.getElementById('servicesContainer');
    const newService = document.createElement('div');
    newService.className = 'service-item';
    newService.style.cssText = 'display: flex; gap: 8px; margin-bottom: 12px; align-items: start;';
    newService.innerHTML = `
        <div style="flex: 1;">
            <input type="text" name="services[${serviceIndex}][title]" placeholder="Service title" style="margin-bottom: 8px;" required>
            <textarea name="services[${serviceIndex}][description]" rows="2" placeholder="Service description" required></textarea>
        </div>
        <button type="button" class="btn" onclick="removeService(this)" style="background: #ef4444; margin-top: 0;">✕</button>
    `;
    container.appendChild(newService);
    serviceIndex++;
}

function removeService(btn) {
    const container = document.getElementById('servicesContainer');
    if (container.children.length > 1) {
        btn.closest('.service-item').remove();
    } else {
        alert('At least one service is required');
    }
}

function addOffer() {
    const container = document.getElementById('offersContainer');
    const newOffer = document.createElement('div');
    newOffer.className = 'offer-item';
    newOffer.style.cssText = 'border: 1px solid var(--border); padding: 16px; border-radius: 8px; margin-bottom: 12px;';
    
    const now = new Date();
    const tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000);
    const startDate = now.toISOString().slice(0, 16);
    const endDate = tomorrow.toISOString().slice(0, 16);
    
    newOffer.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
            <h4 style="margin: 0; font-size: 14px; color: var(--text);">Offer #${offerIndex + 1}</h4>
            <button type="button" class="btn" onclick="removeOffer(this)" style="background: #ef4444; padding: 6px 12px; font-size: 12px;">✕</button>
        </div>
        <label>Offer Image</label>
        <div style="background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 14px;">
            <div style="display: flex; gap: 16px; margin-bottom: 12px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="radio" name="offers[${offerIndex}][image_type]" value="upload" checked onchange="toggleOfferImageInput(${offerIndex})">
                    <span>Upload</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="radio" name="offers[${offerIndex}][image_type]" value="url" onchange="toggleOfferImageInput(${offerIndex})">
                    <span>URL</span>
                </label>
            </div>
            <div id="offer_${offerIndex}_upload">
                <input type="file" name="offers[${offerIndex}][image_file]" accept="image/*" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border);">
            </div>
            <div id="offer_${offerIndex}_url" style="display: none;">
                <input type="url" name="offers[${offerIndex}][image_url]" placeholder="https://example.com/offer.jpg" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border);">
            </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
                <label>Start Date & Time</label>
                <input type="datetime-local" name="offers[${offerIndex}][start_date]" value="${startDate}" required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 14px;">
            </div>
            <div>
                <label>End Date & Time</label>
                <input type="datetime-local" name="offers[${offerIndex}][end_date]" value="${endDate}" required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 14px;">
            </div>
        </div>
    `;
    container.appendChild(newOffer);
    offerIndex++;
}

function removeOffer(btn) {
    const container = document.getElementById('offersContainer');
    if (container.children.length > 0) {
        btn.closest('.offer-item').remove();
    }
}

function addGalleryImage() {
    const container = document.getElementById('galleryContainer');
    const newImage = document.createElement('div');
    newImage.className = 'gallery-item';
    newImage.style.cssText = 'border: 1px solid var(--border); padding: 16px; border-radius: 8px; margin-bottom: 12px;';
    
    newImage.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
            <h4 style="margin: 0; font-size: 14px; color: var(--text);">Image #${galleryIndex + 1}</h4>
            <button type="button" class="btn" onclick="removeGalleryImage(this)" style="background: #ef4444; padding: 6px 12px; font-size: 12px;">✕</button>
        </div>
        <div style="background: #f8fafc; padding: 12px; border-radius: 8px;">
            <div style="display: flex; gap: 16px; margin-bottom: 12px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="radio" name="gallery[${galleryIndex}][image_type]" value="upload" checked onchange="toggleGalleryImageInput(${galleryIndex})">
                    <span>Upload</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="radio" name="gallery[${galleryIndex}][image_type]" value="url" onchange="toggleGalleryImageInput(${galleryIndex})">
                    <span>URL</span>
                </label>
            </div>
            <div id="gallery_${galleryIndex}_upload">
                <input type="file" name="gallery[${galleryIndex}][image_file]" accept="image/*" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border);">
            </div>
            <div id="gallery_${galleryIndex}_url" style="display: none;">
                <input type="url" name="gallery[${galleryIndex}][image_url]" placeholder="https://example.com/image.jpg" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border);">
            </div>
        </div>
    `;
    container.appendChild(newImage);
    galleryIndex++;
}

function removeGalleryImage(btn) {
    btn.closest('.gallery-item').remove();
}

function addPayment() {
    const container = document.getElementById('paymentsContainer');
    const newPayment = document.createElement('div');
    newPayment.className = 'payment-item';
    newPayment.style.cssText = 'border: 1px solid var(--border); padding: 16px; border-radius: 8px; margin-bottom: 12px;';
    newPayment.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
            <h4 style="margin: 0; font-size: 14px; color: var(--text);">Payment #${paymentIndex + 1}</h4>
            <button type="button" class="btn" onclick="removePayment(this)" style="background: #ef4444; padding: 6px 12px; font-size: 12px;">✕</button>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
                <label>Amount (₹)</label>
                <input type="number" name="payments[${paymentIndex}][amount]" step="0.01" min="0">
            </div>
            <div>
                <label>Payment Date</label>
                <input type="date" name="payments[${paymentIndex}][paid_at]" value="{{ now()->format('Y-m-d') }}">
            </div>
        </div>
        <label>Transaction ID</label>
        <input type="text" name="payments[${paymentIndex}][transaction_id]" placeholder="Optional">
        <label>Description</label>
        <textarea name="payments[${paymentIndex}][description]" rows="2" placeholder="Payment notes (optional)"></textarea>
    `;
    container.appendChild(newPayment);
    paymentIndex++;
}

function removePayment(btn) {
    btn.closest('.payment-item').remove();
}
</script>
@endsection
