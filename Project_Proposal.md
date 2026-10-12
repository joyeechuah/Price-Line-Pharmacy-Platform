## 1. Student Details

| Field | Your Answer |
|---|---|
| Candidate Name | Chuah Jo Yee |
| NRIC Number | ... |
| Date Submitted | DD/MM/YYYY |

---

## 2. Project Title

**Price Line Pharmacy Platform**

### One-line summary

A web application where customers browse, search and order pharmacy products online, with a cart, checkout and pharmacist consultation requests.

---

## 3. Problem Statement & Purpose

> What problem does your application solve? Who is it for? Why is it useful?

Customers often need to visit a pharmacy in person to buy everyday medicines and health products, check whether an item is in stock, or ask a pharmacist a question. This platform is for pharmacy customers who want to shop from home, and for pharmacy staff who need one place to manage products, stock and customer enquiries. Customers can search products by category, order online and request a call from a pharmacist. Staff get separate, role-limited tools, so each person only handles the work they are responsible for.

---

## 4. Tech Stack

> Required: HTML, CSS, PHP, MySQL.

| Layer | Technology |
|---|---|
| Markup | HTML |
| Styling | CSS, Bootstrap 5 (homepage banner carousel only) |
| Client-side | JavaScript |
| Server-side | PHP |
| Database | MySQL |

---

## 5. Types of Users (Roles)

> Minimum **3 roles**. Each role must have different levels of access.

| Role | Description |
|---|---|
| Admin | Full access to the staff panel. Views user accounts, creates staff accounts, manages products, views all orders and handles consultation requests. |
| Pharmacist | Views customer consultation requests and marks them as contacted after calling the customer. Can view products but not change them. |
| Storekeeper | Adds, edits and removes products and keeps stock levels up to date. |
| Customer | Browses and searches products, adds them to the cart, checks out, views their own orders and submits consultation requests. |

### Role-Based Access Matrix

> Mark what each role can do. Add or remove rows to match your features.

| Feature / Page | Admin | Pharmacist | Storekeeper | Customer | Guest (not logged in) |
|---|:---:|:---:|:---:|:---:|:---:|
| Register account | ❌ | ❌ | ❌ | ❌ | ✅ |
| Login | ✅ | ✅ | ✅ | ✅ | ✅ |
| Browse and search products | ✅ | ✅ | ✅ | ✅ | ✅ |
| Add to cart and manage cart | ❌ | ❌ | ❌ | ✅ | ✅ |
| Checkout (place an order) | ❌ | ❌ | ❌ | ✅ | ❌ |
| View own orders | ❌ | ❌ | ❌ | ✅ | ❌ |
| View all orders | ✅ | ❌ | ❌ | ❌ | ❌ |
| Manage products and stock | ✅ | ❌ | ✅ | ❌ | ❌ |
| Submit consultation request | ❌ | ❌ | ❌ | ✅ | ❌ |
| Handle consultation requests | ✅ | ✅ | ❌ | ❌ | ❌ |
| View users and create staff accounts | ✅ | ❌ | ❌ | ❌ | ❌ |

A guest can fill a cart before logging in. The cart is saved to their account when they log in or register, and they must be logged in as a customer to check out.

---

## 6. Features

### 6.1 Core Features (must have)

- [ ] User registration and login
- [ ] Role-based access control (each role sees/does different things)
- [ ] Data management (Create, Read, Update, Delete)
- [ ] Add to Cart product(s)
- [ ] Checkout Page

### 6.2 Extra Features (nice to have)

- [ ] Product search and category filter
- [ ] Consultation request form

### 6.3 Feature Descriptions

> Briefly explain each core feature: what it does and which role uses it.

| Feature | Description | Role(s) |
|---|---|---|
| User registration and login | Customers register themselves. Admins create staff accounts. All users log in with email and password, and are sent to the correct area for their role. | Admin, Pharmacist, Storekeeper, Customer |
| Role-based access control | Each role only sees its own pages: customers shop, storekeepers manage products and stock, pharmacists handle consultations, and admins manage everything. | Admin, Pharmacist, Storekeeper, Customer |
| Data management (CRUD) | Admins and storekeepers add, view, edit and delete products, including name, price, stock, category and image. A product that has already been ordered is archived (hidden from the shop) instead of deleted, so the order history is kept. | Admin, Storekeeper |
| Add to Cart | Guests and customers add products to their cart, change quantities or remove items, and see the running total. | Guest, Customer |
| Checkout Page | Customers review their cart, enter delivery details and place the order. Stock goes down automatically once the order is placed. | Customer |

---

## 7. Data Management System

> Which data can users create, view, edit and delete? Who is allowed to do what?

| Data / Entity | Create | Read | Update | Delete |
|---|---|---|---|---|
| Users | Guest (registers as Customer), Admin (creates staff accounts) | Admin (all users) | None | None |
| Products | Admin, Storekeeper | Everyone, including Guest (active products only) | Admin, Storekeeper | Admin, Storekeeper (archived instead if already ordered) |
| Cart items | Guest, Customer | Guest, Customer (own cart) | Guest, Customer (change quantity) | Guest, Customer (remove item) |
| Orders | Customer (at checkout) | Customer (own orders), Admin (all orders) | None | None |
| Consultation requests | Customer | Customer (own requests), Pharmacist, Admin | Pharmacist, Admin (mark as contacted) | Admin |

A guest's cart is kept in the session. A customer's cart is kept in the `cart_items` table.

---

## 8. Database Design

> Minimum **4 tables** with at least **3 linkages** (foreign keys) between them.

### Entity Relationship Diagram (ERD)

```mermaid
erDiagram
    USERS {
        int user_id PK
        varchar name
        varchar email
        varchar password_hash
        enum role "admin, pharmacist, storekeeper, customer"
        datetime created_at
    }
    PRODUCTS {
        int product_id PK
        varchar name
        text description
        varchar category
        decimal price
        int stock
        varchar image_url
        tinyint is_active "1 = on sale, 0 = archived"
    }
    CART_ITEMS {
        int cart_item_id PK
        int user_id FK
        int product_id FK
        int quantity
    }
    ORDERS {
        int order_id PK
        int user_id FK
        varchar delivery_address
        varchar phone
        decimal total_amount
        datetime created_at
    }
    ORDER_ITEMS {
        int order_item_id PK
        int order_id FK
        int product_id FK
        int quantity
        decimal price_at_purchase
    }
    CONSULTATIONS {
        int consultation_id PK
        int user_id FK
        varchar contact_number
        datetime preferred_datetime
        text question
        enum status "pending, contacted"
        datetime created_at
    }

    USERS ||--o{ CART_ITEMS : "has"
    PRODUCTS ||--o{ CART_ITEMS : "added to"
    USERS ||--o{ ORDERS : "places"
    ORDERS ||--|{ ORDER_ITEMS : "contains"
    PRODUCTS ||--o{ ORDER_ITEMS : "appears in"
    USERS ||--o{ CONSULTATIONS : "submits"
```

---

## 9. Use Case Diagram

> Show the actors (roles) and what each can do in the system.

```mermaid
flowchart LR
    G["👤 Guest"]
    C["👤 Customer"]
    S["👤 Storekeeper"]
    P["👤 Pharmacist"]
    A["👤 Admin"]

    subgraph SYS["Price Line Pharmacy Platform"]
        UC1(["Register"])
        UC2(["Log in"])
        UC3(["Browse and search products"])
        UC4(["Manage cart"])
        UC5(["Checkout"])
        UC6(["View own orders"])
        UC7(["Submit consultation request"])
        UC8(["Manage products and stock"])
        UC9(["Handle consultation requests"])
        UC10(["View users and create staff accounts"])
        UC11(["View all orders"])
    end

    G --- UC1
    G --- UC2
    G --- UC3
    G --- UC4

    C --- UC2
    C --- UC3
    C --- UC4
    C --- UC5
    C --- UC6
    C --- UC7

    UC2 --- S
    UC8 --- S

    UC2 --- P
    UC9 --- P

    UC2 --- A
    UC8 --- A
    UC9 --- A
    UC10 --- A
    UC11 --- A
```

Staff can also browse and search products, but they cannot use the cart.

### Flowchart 1: Login and role-based routing

```mermaid
flowchart TD
    A1(["Start"]) --> A2[/"Enter email and password"/]
    A2 --> A3["Find the email in the users table"]
    A3 --> A4{"Email found and password matches the saved hash?"}
    A4 -- No --> A5[/"Show: Invalid email or password"/]
    A5 --> A2
    A4 -- Yes --> A6["Save user ID, name and role in the session"]
    A6 --> A7{"Which role?"}
    A7 -- Admin --> A8["Open admin dashboard"]
    A7 -- Pharmacist --> A9["Open pharmacist dashboard"]
    A7 -- Storekeeper --> A10["Open manage products page"]
    A7 -- Customer --> A11["Move guest cart items into the cart_items table"]
    A11 --> A12["Open products page, or the page that asked for login"]
    A8 --> A13(["End"])
    A9 --> A13
    A10 --> A13
    A12 --> A13
```

### Flowchart 2: Shopping and checkout

```mermaid
flowchart TD
    B1(["Start"]) --> B2["Browse or search products"]
    B2 --> B3[/"Choose a product and quantity"/]
    B3 --> B4{"Enough stock?"}
    B4 -- No --> B5[/"Show: Not enough stock"/]
    B5 --> B2
    B4 -- Yes --> B6["Add item to cart (guest: session, customer: cart_items table)"]
    B6 --> B7{"Continue shopping?"}
    B7 -- Yes --> B2
    B7 -- No --> B8["Open checkout"]
    B8 --> B9{"Logged in as a customer?"}
    B9 -- No --> B10["Log in or register (the guest cart is saved to the account)"]
    B10 --> B8
    B9 -- Yes --> B11[/"Enter delivery address and phone number"/]
    B11 --> B12{"Cart not empty and stock still enough?"}
    B12 -- No --> B13[/"Show error and return to cart"/]
    B13 --> B2
    B12 -- Yes --> B14[("Save order and order items, reduce product stock, empty the cart")]
    B14 --> B15[/"Show the new order in My Orders"/]
    B15 --> B16(["End"])
```

---

## 10. Presentation Checklist

- [ ] Can explain the purpose of the application
- [ ] Can justify design choices (why this database structure, why these roles)
- [ ] Can demo every role
- [ ] Can answer questions about my own code
- [ ] Submitted on time
