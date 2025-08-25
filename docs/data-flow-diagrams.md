# Neema Gospel Data Flow Diagrams

## Overview
This document contains comprehensive data flow diagrams for the Neema Gospel Laravel application, covering authentication, event management, order tracking, and payment processing.

---

## 1. Authentication Data Flow

```mermaid
sequenceDiagram
    participant Client as Client App
    participant API as Laravel API
    participant AuthController as AuthController
    participant Sanctum as Sanctum
    participant DB as Database
    participant Cache as Redis Cache

    Client->>API: POST /api/auth/register
    API->>AuthController: register()
    AuthController->>DB: Create user record
    AuthController->>Sanctum: Generate token
    Sanctum->>DB: Store token
    AuthController->>Cache: Cache user data
    AuthController-->>Client: {user, token}

    Client->>API: POST /api/auth/login
    API->>AuthController: login()
    AuthController->>DB: Validate credentials
    AuthController->>Sanctum: Generate token
    Sanctum->>DB: Store token
    AuthController->>Cache: Cache user data
    AuthController-->>Client: {user, token}

    Client->>API: POST /api/auth/logout
    API->>AuthController: logout()
    AuthController->>Sanctum: Revoke token
    AuthController->>Cache: Clear user cache
    AuthController-->>Client: Success response
```

---

## 2. Event Management Data Flow

```mermaid
sequenceDiagram
    participant Client as Client App
    participant API as Laravel API
    participant EventController as EventController
    participant Event as Event Model
    participant Cache as Redis Cache
    participant Storage as File Storage

    %% Create Event Flow
    Client->>API: POST /api/events
    API->>EventController: store()
    EventController->>Event: Validate & create event
    EventController->>Storage: Upload picture
    EventController->>Cache: Clear events cache
    EventController-->>Client: {event}

    %% Get Events Flow
    Client->>API: GET /api/events
    API->>EventController: index()
    EventController->>Cache: Check cache
    alt Cache Miss
        EventController->>Event: Query events
        EventController->>Cache: Store results
    end
    EventController-->>Client: {events}

    %% Update Event Flow
    Client->>API: PUT /api/events/{id}
    API->>EventController: update()
    EventController->>Event: Find event
    EventController->>Event: Update fields
    EventController->>Storage: Handle picture update
    EventController->>Cache: Clear cache
    EventController-->>Client: {updated_event}

    %% Delete Event Flow
    Client->>API: DELETE /api/events/{id}
    API->>EventController: destroy()
    EventController->>Event: Find & delete
    EventController->>Storage: Delete picture
    EventController->>Cache: Clear cache
    EventController-->>Client: Success response
```

---

## 3. Order Tracking Data Flow

```mermaid
sequenceDiagram
    participant Client as Client App
    participant API as Laravel API
    participant TrackingController as TrackingController
    participant OrderTrackingService as OrderService
    participant PaymentGateway as PaymentGateway
    participant DB as Database
    participant Cache as Redis Cache

    %% Create Tracking Order
    Client->>API: POST /api/tracking/orders
    API->>TrackingController: store()
    TrackingController->>OrderService: createOrder()
    OrderService->>PaymentGateway: Initialize payment
    PaymentGateway-->>OrderService: Payment URL
    OrderService->>DB: Save order with pending status
    OrderService->>Cache: Cache order data
    TrackingController-->>Client: {order, payment_url}

    %% Track Order Status
    Client->>API: GET /api/tracking/orders/{tracking_number}
    API->>TrackingController: show()
    TrackingController->>Cache: Check cache
    alt Cache Miss
        TrackingController->>OrderService: getOrder()
        OrderService->>DB: Query order
        OrderService->>Cache: Store result
    end
    TrackingController-->>Client: {order_details}

    %% Update Order Status
    PaymentGateway->>API: POST /api/tracking/webhook
    API->>TrackingController: webhook()
    TrackingController->>OrderService: updateStatus()
    OrderService->>DB: Update order status
    OrderService->>Cache: Update cache
    OrderService-->>PaymentGateway: Acknowledge
```

---

## 4. Payment Processing Data Flow

```mermaid
sequenceDiagram
    participant Client as Client App
    participant API as Laravel API
    participant PaymentController as PaymentController
    participant PaymentGatewayService as PaymentService
    participant PaymentGateway as External Gateway
    participant DB as Database
    participant Cache as Redis Cache

    %% Payment Flow
    Client->>API: POST /api/payments/initiate
    API->>PaymentController: initiatePayment()
    PaymentController->>PaymentService: createPayment()
    PaymentService->>DB: Create payment record
    PaymentService->>PaymentGateway: Initiate transaction
    PaymentGateway-->>PaymentService: Transaction ID
    PaymentService->>DB: Update with transaction ID
    PaymentService->>Cache: Cache payment data
    PaymentController-->>Client: {payment_details, redirect_url}

    %% Payment Callback
    PaymentGateway->>API: POST /api/payments/callback
    API->>PaymentController: handleCallback()
    PaymentController->>PaymentService: verifyPayment()
    PaymentService->>PaymentGateway: Verify transaction
    PaymentGateway-->>PaymentService: Transaction status
    PaymentService->>DB: Update payment status
    PaymentService->>Cache: Update cache
    PaymentService->>DB: Update related order
    PaymentController-->>PaymentGateway: Redirect to success/failure

    %% Payment Status Check
    Client->>API: GET /api/payments/{transaction_id}
    API->>PaymentController: getStatus()
    PaymentController->>Cache: Check cache
    alt Cache Miss
        PaymentController->>PaymentService: getPayment()
        PaymentService->>DB: Query payment
        PaymentService->>Cache: Store result
    end
    PaymentController-->>Client: {payment_status}
```

---

## 5. Cache Management Data Flow

```mermaid
flowchart TD
    A[Client Request] --> B{Cache Check}
    B -->|Cache Hit| C[Return Cached Data]
    B -->|Cache Miss| D[Query Database]
    D --> E[Process Data]
    E --> F[Store in Cache]
    F --> G[Return Response]
    
    H[Data Update] --> I[Invalidate Cache]
    I --> J[Update Database]
    J --> K[Warm Cache]
    K --> L[Return Updated Data]

    style C fill:#90EE90
    style G fill:#90EE90
    style L fill:#90EE90
```

---

## 6. File Upload Data Flow

```mermaid
sequenceDiagram
    participant Client as Client App
    participant API as Laravel API
    participant Controller as Controller
    participant Storage as File Storage
    participant DB as Database
    participant Cache as Redis Cache

    Client->>API: POST /api/upload with file
    API->>Controller: handleUpload()
    Controller->>Storage: Validate file type/size
    Controller->>Storage: Generate unique filename
    Controller->>Storage: Store file
    Storage-->>Controller: File path
    Controller->>DB: Save file reference
    Controller->>Cache: Clear related cache
    Controller-->>Client: {file_url, file_path}
```

---

## 7. API Rate Limiting Data Flow

```mermaid
flowchart LR
    A[Client Request] --> B{Rate Limit Check}
    B -->|Under Limit| C[Process Request]
    B -->|Over Limit| D[Return 429 Error]
    
    C --> E[Update Rate Counter]
    E --> F[Return Response]
    
    style C fill:#90EE90
    style D fill:#FFB6C1
    style F fill:#90EE90
```

---

## 8. Error Handling Data Flow

```mermaid
flowchart TD
    A[API Request] --> B{Error Occurs?}
    B -->|No| C[Return Success Response]
    B -->|Yes| D{Error Type}
    
    D -->|Validation| E[Return 422 with errors]
    D -->|Authentication| F[Return 401/403]
    D -->|Not Found| G[Return 404]
    D -->|Server Error| H[Log Error]
    H --> I[Return 500 with message]
    
    style C fill:#90EE90
    style E fill:#FFB6C1
    style F fill:#FFB6C1
    style G fill:#FFB6C1
    style I fill:#FFB6C1
```

---

## 9. Database Transaction Flow

```mermaid
sequenceDiagram
    participant Controller as Controller
    participant DB as Database
    participant Service as Service Layer
    participant Cache as Cache

    Controller->>DB: BEGIN TRANSACTION
    Controller->>Service: Process Business Logic
    Service->>DB: Insert/Update Records
    Service->>Cache: Update Cache
    alt Success
        Controller->>DB: COMMIT
        Controller-->>Client: Success Response
    else Failure
        Controller->>DB: ROLLBACK
        Controller-->>Client: Error Response
    end
```

---

## 10. API Response Format Flow

```mermaid
flowchart TD
    A[Controller Logic] --> B[Format Response]
    B --> C{Response Type}
    
    C -->|Success| D[Add success: true]
    C -->|Error| E[Add success: false]
    
    D --> F[Add data/message]
    E --> G[Add error details]
    
    F --> H[Add metadata]
    G --> H
    
    H --> I[Return JSON Response]
    
    style D fill:#90EE90
    style E fill:#FFB6C1
    style I fill:#90EE90
```

---

## Key Data Flow Patterns

### **Caching Strategy**
- **Cache-aside pattern** for read-heavy operations
- **Cache invalidation** on data updates
- **Cache warming** for frequently accessed data

### **Error Handling**
- **Centralized exception handling** via Handler.php
- **Consistent error response format** across all endpoints
- **Detailed validation errors** for client-side handling

### **Security Flows**
- **Token-based authentication** with Laravel Sanctum
- **Rate limiting** per user/IP
- **Input validation** at controller level
- **File upload restrictions** (type, size, MIME)

### **Performance Optimizations**
- **Database indexing** on frequently queried fields
- **Eager loading** to prevent N+1 queries
- **Pagination** for large datasets
- **CDN integration** for static assets

### **Data Validation Flow**
1. **Client-side validation** (React app)
2. **API-level validation** (Form Requests)
3. **Database-level constraints** (migrations)
4. **Business rule validation** (service layer)
