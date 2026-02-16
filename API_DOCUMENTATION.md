# Arugil API Documentation

## Base URL
```
https://arugil.app/api/v1
```

## Authentication
The API uses **Laravel Sanctum** for authentication. Include the token in the `Authorization` header:
```
Authorization: Bearer {token}
```

---

## Public Endpoints (No Authentication Required)

### Authentication

#### Register User
**POST** `/auth/register`

Request body:
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+91 9876543210",
    "password": "password123",
    "password_confirmation": "password123"
}
```

Response:
```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+91 9876543210",
        "role": "user"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

#### Login
**POST** `/auth/login`

Request body:
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

Response:
```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+91 9876543210",
        "role": "user"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

---

### Categories

#### List All Categories
**GET** `/categories`

Query parameters:
- `per_page`: Items per page (default: 15)
- `page`: Page number (default: 1)

Response:
```json
{
    "data": [
        {
            "id": 1,
            "name": "Restaurants",
            "icon": "🍽️",
            "children": [
                {
                    "id": 2,
                    "name": "Fast Food",
                    "icon": "🍔"
                },
                {
                    "id": 3,
                    "name": "Casual Dining",
                    "icon": "🍽️"
                }
            ]
        }
    ],
    "pagination": {
        "total": 50,
        "per_page": 15,
        "current_page": 1,
        "last_page": 4
    }
}
```

#### Get Businesses in Category
**GET** `/categories/{category_id}/businesses`

Query parameters:
- `per_page`: Items per page (default: 15)
- `page`: Page number (default: 1)

Response:
```json
{
    "data": [
        {
            "id": 1,
            "name": "Pizza Palace",
            "category_id": 2,
            "description": "Authentic Italian pizza",
            "phone": "+91 9876543210",
            "whatsapp": "+91 9876543210",
            "address": "123 Main St",
            "latitude": 28.6139,
            "longitude": 77.2090,
            "views": 150,
            "is_featured": true
        }
    ]
}
```

---

### Businesses

#### List Businesses
**GET** `/businesses`

Query parameters:
- `category_id`: Filter by category
- `featured`: true/false (default: null)
- `search`: Search by name
- `per_page`: Items per page (default: 15)
- `page`: Page number

Response:
```json
{
    "data": [
        {
            "id": 1,
            "name": "Pizza Palace",
            "category_id": 2,
            "category": {
                "id": 2,
                "name": "Fast Food"
            },
            "owner": {
                "id": 5,
                "name": "John Smith",
                "phone": "+91 9876543211"
            },
            "description": "Best pizza in town",
            "phone": "+91 9876543210",
            "whatsapp": "+91 9876543210",
            "address": "123 Main St",
            "latitude": 28.6139,
            "longitude": 77.2090,
            "services": [
                {
                    "title": "Dine In",
                    "description": "Comfortable seating for 50 people"
                }
            ],
            "offers": [
                {
                    "image_url": "https://...",
                    "start_date": "2026-02-16",
                    "end_date": "2026-02-23"
                }
            ],
            "views": 150,
            "is_featured": true,
            "expiry_date": "2027-02-16"
        }
    ],
    "pagination": {
        "total": 245,
        "per_page": 15,
        "current_page": 1,
        "last_page": 17
    }
}
```

#### Get Business Details
**GET** `/business/{business_id}`

Response:
```json
{
    "id": 1,
    "name": "Pizza Palace",
    "category": {
        "id": 2,
        "name": "Fast Food"
    },
    "owner": {
        "id": 5,
        "name": "John Smith",
        "phone": "+91 9876543211"
    },
    "owner_image_url": "https://storage.../owner.jpg",
    "owner_name": "John Smith",
    "years_of_business": 5,
    "description": "Best pizza in town",
    "about_title": "About Us",
    "phone": "+91 9876543210",
    "whatsapp": "+91 9876543210",
    "email": "info@pizzapalace.com",
    "website": "https://pizzapalace.com",
    "address": "123 Main St",
    "latitude": 28.6139,
    "longitude": 77.2090,
    "image_url": "https://storage.../business.jpg",
    "services": [
        {
            "title": "Dine In",
            "description": "Comfortable seating for 50 people"
        },
        {
            "title": "Takeaway",
            "description": "Quick takeaway service"
        }
    ],
    "offers": [
        {
            "image_url": "https://...",
            "start_date": "2026-02-16T10:00:00",
            "end_date": "2026-02-23T23:59:59"
        }
    ],
    "images": [
        {
            "id": 1,
            "image_url": "https://storage.../gallery1.jpg"
        },
        {
            "id": 2,
            "image_url": "https://storage.../gallery2.jpg"
        }
    ],
    "reviews": [
        {
            "id": 1,
            "user_id": 10,
            "user": {
                "name": "Jane Doe"
            },
            "rating": 5,
            "comment": "Excellent service!",
            "created_at": "2026-02-15T10:30:00"
        }
    ],
    "views": 150,
    "is_featured": true,
    "expiry_date": "2027-02-16"
}
```

#### Featured Businesses
**GET** `/featured`

Query parameters:
- `limit`: Number of results (default: 10)

Response:
```json
{
    "data": [
        {
            "id": 1,
            "name": "Pizza Palace",
            "category": { "id": 2, "name": "Fast Food" },
            "image_url": "https://...",
            "is_featured": true
        }
    ]
}
```

#### Nearby Businesses
**GET** `/nearby`

Query parameters:
- `latitude`: User latitude (required)
- `longitude`: User longitude (required)
- `radius`: Search radius in km (default: 5)
- `category_id`: Filter by category

Response:
```json
{
    "data": [
        {
            "id": 1,
            "name": "Pizza Palace",
            "latitude": 28.6139,
            "longitude": 77.2090,
            "distance": 0.5
        }
    ]
}
```

---

### Reviews

#### List Business Reviews
**GET** `/business/{business_id}/reviews`

Query parameters:
- `per_page`: Items per page (default: 10)
- `page`: Page number

Response:
```json
{
    "data": [
        {
            "id": 1,
            "user_id": 10,
            "user": {
                "name": "Jane Doe"
            },
            "rating": 5,
            "comment": "Excellent service and delicious food!",
            "created_at": "2026-02-15T10:30:00"
        }
    ],
    "pagination": {
        "total": 45,
        "per_page": 10,
        "current_page": 1,
        "last_page": 5
    }
}
```

---

### Jobs

#### List Jobs
**GET** `/jobs`

Query parameters:
- `business_id`: Filter by business
- `status`: active/expired
- `per_page`: Items per page (default: 15)

Response:
```json
{
    "data": [
        {
            "id": 1,
            "business_id": 1,
            "business": {
                "id": 1,
                "name": "Pizza Palace"
            },
            "title": "Pizza Chef",
            "description": "Experienced pizza chef needed",
            "salary": "₹25,000 - ₹30,000",
            "expiry_date": "2026-03-16",
            "status": "active",
            "created_at": "2026-02-10T08:00:00"
        }
    ],
    "pagination": {
        "total": 120,
        "per_page": 15,
        "current_page": 1,
        "last_page": 8
    }
}
```

---

### Advertisements

#### List Ads
**GET** `/ads`

Query parameters:
- `placement`: home/category/detail
- `per_page`: Items per page (default: 10)

Response:
```json
{
    "data": [
        {
            "id": 1,
            "title": "Summer Sale",
            "category_id": 2,
            "image_url": "https://...",
            "link": "https://example.com",
            "placement": "home",
            "clicks": 145,
            "start_date": "2026-02-01",
            "end_date": "2026-02-28"
        }
    ]
}
```

#### Track Ad Click
**POST** `/ads/{ad_id}/click`

Response:
```json
{
    "message": "Click recorded",
    "clicks": 146
}
```

---

### Emergency Contacts

#### List Emergency Numbers
**GET** `/emergency`

Response:
```json
{
    "data": [
        {
            "id": 1,
            "title": "Police",
            "phone": "100",
            "category": "Safety"
        },
        {
            "id": 2,
            "title": "Ambulance",
            "phone": "102",
            "category": "Medical"
        }
    ]
}
```

---

## Protected Endpoints (Authentication Required)

### User

#### Get Current User
**GET** `/user`

Headers:
```
Authorization: Bearer {token}
```

Response:
```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+91 9876543210",
    "role": "user",
    "created_at": "2026-02-01T10:00:00"
}
```

#### Logout
**POST** `/auth/logout`

Headers:
```
Authorization: Bearer {token}
```

Response:
```json
{
    "message": "Logged out successfully"
}
```

---

### Businesses

#### Create Business
**POST** `/business`

Headers:
```
Authorization: Bearer {token}
Content-Type: application/json
```

Request body:
```json
{
    "category_id": 2,
    "name": "My Restaurant",
    "description": "Great food",
    "owner_name": "John Doe",
    "years_of_business": 5,
    "phone": "+91 9876543210",
    "whatsapp": "+91 9876543210",
    "address": "123 Main St",
    "latitude": 28.6139,
    "longitude": 77.2090,
    "image_url": "https://...",
    "services": [
        {
            "title": "Dine In",
            "description": "Comfortable seating"
        }
    ]
}
```

Response:
```json
{
    "id": 25,
    "name": "My Restaurant",
    "category_id": 2,
    "is_approved": false,
    "created_at": "2026-02-16T12:00:00"
}
```

#### Update Business
**PUT** `/business/{business_id}`

Headers:
```
Authorization: Bearer {token}
```

Request body: (same as create)

Response:
```json
{
    "id": 25,
    "name": "My Restaurant Updated",
    "updated_at": "2026-02-16T12:30:00"
}
```

---

### Reviews

#### Create Review
**POST** `/review`

Headers:
```
Authorization: Bearer {token}
```

Request body:
```json
{
    "business_id": 1,
    "rating": 5,
    "comment": "Excellent service and food!"
}
```

Response:
```json
{
    "id": 100,
    "business_id": 1,
    "user_id": 1,
    "rating": 5,
    "comment": "Excellent service and food!",
    "created_at": "2026-02-16T12:00:00"
}
```

---

### Jobs

#### Create Job
**POST** `/jobs`

Headers:
```
Authorization: Bearer {token}
```

Request body:
```json
{
    "business_id": 1,
    "title": "Senior Chef",
    "description": "Looking for experienced chef",
    "salary": "₹40,000 - ₹50,000",
    "expiry_date": "2026-03-16"
}
```

Response:
```json
{
    "id": 50,
    "business_id": 1,
    "title": "Senior Chef",
    "status": "active",
    "created_at": "2026-02-16T12:00:00"
}
```

#### Apply for Job
**POST** `/job/apply`

Headers:
```
Authorization: Bearer {token}
```

Request body:
```json
{
    "job_id": 50,
    "message": "I am interested in this position"
}
```

Response:
```json
{
    "id": 15,
    "job_id": 50,
    "user_id": 1,
    "status": "pending",
    "created_at": "2026-02-16T12:00:00"
}
```

---

## Error Responses

### 400 Bad Request
```json
{
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required"]
    }
}
```

### 401 Unauthorized
```json
{
    "message": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
    "message": "This action is unauthorized"
}
```

### 404 Not Found
```json
{
    "message": "Resource not found"
}
```

### 500 Server Error
```json
{
    "message": "Internal server error"
}
```

---

## Rate Limiting

- General endpoints: 60 requests per minute
- Auth endpoints: 5 requests per minute
- Search endpoints: 30 requests per minute

---

## Pagination

All list endpoints support pagination:

```json
{
    "data": [...],
    "pagination": {
        "total": 100,
        "per_page": 15,
        "current_page": 1,
        "last_page": 7,
        "from": 1,
        "to": 15
    }
}
```

---

## Currency

All monetary values are in **Indian Rupees (₹)**.

---

## Best Practices

1. **Always include valid authentication headers** for protected endpoints
2. **Use pagination** for large datasets
3. **Cache responses** on client side when appropriate
4. **Handle rate limiting** with exponential backoff
5. **Validate input** before sending requests
6. **Use appropriate HTTP methods** (GET, POST, PUT, DELETE)
7. **Include error handling** for all API calls

---

## Support

For API support, contact: api-support@arugil.app
