# User Story Task Breakdown

# TODO - Piper

## TODO
### User 1 Alex (M, 28) Priority High, initial estimate 2 days As a User of the app, I want to create an account. So that I can place food orders.

Task 1:
Create account creation page with ui to enter personal information - 1 day

Task 2:
set up back end infrastructure to store account information - 1 day

Total estimate - 2 days

## TODO
### User 2 Sophie (F, 34) Priority High, estimate 1 day As a User of the app, I want to log in securely. So that my account is protected.

Task 1:
Create log in page with ui to enter email and password - 1 day

Task 2:
Create functionality to accept passwords using encrypted keys - 1 day

Total estimate - 2 days

## TODO
### User 3 Liam (M, 22) Priority High, initial estimate 2 days As a User of the app, I want to browse nearby restaurants. So that I can see available options.

Task 1:
Create a database of restaurants that have signed up for the delivery service - Half a day

Task 2:
Create functionality to filter restaurants based on proximity to the user's listed address - 1 days

Task 3:
Connect the database to the site and display the list to the user - 1 day

Total estimate - 2.5 days

## TODO
### User 4 Olivia (F, 30) Priority High, initial estimate 2 days As a User of the app, I want to view restaurant menus. So that I can decide what to order.

Task 1:
Create a database of the available items for each restaurant - 1 day

Task 2:
Connect the database to the site for each restaurant - 1 day

Total estimate - 2 days


---

# TODO - Terry

## TODO
## User Story 5 – Filter Restaurants by Cuisine

**Title:** Filter Restaurants by Cuisine  
**Priority:** High  
**User:** Amelia (28)  
**Total Estimate:** 2 Days  

### Task 1 – Update Database Schema  
Add cuisine field to restaurant table (if not already present) and ensure proper indexing for filtering performance.  
**Estimated Time:** 0.5 days  

### Task 2 – Backend API Filtering Logic  
Modify or create API endpoint to allow filtering by cuisine parameter (e.g., `?cuisine=Italian`).  
**Estimated Time:** 0.5 days  

### Task 3 – Frontend Filter UI  
Add dropdown or selectable cuisine filters in the app interface.  
**Estimated Time:** 0.5 days  

### Task 4 – Connect Frontend to Backend  
Connect filter UI to API and test filtering functionality.  
**Estimated Time:** 0.5 days  

**Total: 2 Days**

---
## TODO
## User Story 6 – Apply Promo Codes

**Title:** Apply Promo Codes  
**Priority:** High  
**User:** Michael (42)  
**Total Estimate:** 2 Days  

### Task 1 – Create Promo Code Database Table  
Design schema including code, expiry date, discount percentage, usage limit, and validation rules.  
**Estimated Time:** 0.5 days  

### Task 2 – Backend Validation Logic  
Create logic to validate promo codes (active, not expired, usage limit not exceeded).  
**Estimated Time:** 0.5 days  

### Task 3 – Checkout UI Field  
Add input field for promo code entry in checkout screen.  
**Estimated Time:** 0.5 days  

### Task 4 – Apply Discount Calculation  
Modify order total calculation to apply validated discount and update UI in real time.  
**Estimated Time:** 0.5 days  

**Total: 2 Days**

---

## TODO
## User Story 7 – Search for Gluten-Free Only Food

**Title:** Search for Gluten-Free Only Food  
**Priority:** High  
**User:** Leo (40)  
**Total Estimate:** 10 Days  

### Task 1 – Requirements & Research  
Define what qualifies as gluten-free, confirm tagging standards, and define validation rules.  
**Estimated Time:** 1 day  

### Task 2 – Database Schema Update  
Add dietary tags to menu items (e.g., gluten-free flag or dietary attributes table).  
**Estimated Time:** 1 day  

### Task 3 – Data Population & Migration  
Update existing menu items with correct dietary labels.  
**Estimated Time:** 2 days  

### Task 4 – Backend Filtering Logic  
Create API logic to filter menu items by gluten-free flag only.  
**Estimated Time:** 2 days  

### Task 5 – Frontend UI Update  
Add gluten-free toggle or checkbox in search/filter section.  
**Estimated Time:** 1 day  

### Task 6 – Safety Validation & Edge Case Handling  
Handle cross-contamination disclaimers, incomplete data, and false positives.  
**Estimated Time:** 1.5 days  

### Task 7 – Testing (Unit + Integration + Edge Cases)  
Test strict filtering to ensure non-gluten items never appear in results.  
**Estimated Time:** 1.5 days  

**Total: 10 Days**

---

## TODO
## User Story 8 – Secure Login Implementation

**Title:** Secure Login Implementation  
**Priority:** High  
**User:** Sophie (34)  
**Total Estimate:** 1 Day  

### Task 1 – Backend Authentication Setup  
Implement secure authentication (password hashing, token/session management).  
**Estimated Time:** 0.5 days  

### Task 2 – Login UI Screen  
Create login form with email/username and password fields.  
**Estimated Time:** 0.5 days  

**Total: 1 Day**

---

# TODO - Ben

## TODO
## 9. Olivia (F, 30)
**Priority:** High  
**Estimate:** 2 days  

**User Story:**  
As a User of the app, I want to view restaurant menus so that I can decide what to order.

### Tasks
- **Task 1:** Identify restaurant data structure (restaurant names, menus, items, categories) – 1 day  
- **Task 2:** User interface designs (search and filter) – 1 day  

Total - 2 days
---

## TODO
## 10. Ethan (M, 32)
**Priority:** High  
**Estimate:** 3 days  

**User Story:**  
As a User of the app, I want to pay with debit or credit card so that I can complete my purchase.

### Tasks
- **Task 1:** Implement payment system backend – 1 day  
- **Task 2:** Payment user interface (front end) – 1 day  

Total - 2 days
---

## TODO
## 11. Ava (F, 29)
**Priority:** High  
**Estimate:** 2 days  

**User Story:**  
As a User of the app, I want to enter my delivery address so that my food arrives correctly.

### Tasks
- **Task 1:** Configure database backend to store user data values – 1/2 day  
- **Task 2:** User interface design (form for user to enter address details when ordering) – 1/2 day  

Total - 1 day
---

## TODO
## 12. Isabella (F, 24)
**Priority:** High  
**Estimate:** 2 days  

**User Story:**  
As a User of the app, I want to search for restaurants so that I can quickly find them.

### Tasks
- **Task 1:** Identify restaurant data structure (restaurant names) – 1 day  
- **Task 2:** User interface designs (search and filter) – 1 day  

Total - 2 days

---

# TODO - Lachlan

## TODO
## User Story 13 – View Order History

**Title:** View Order History  
**Priority:** High  
**Total Estimate:** 2 days  

### Task 1 – Create Order History Class
Manage past orders and details.  
**Estimated Time:** 1 day  

### Task 2 – Create User Interface
Display order history and reorder options.  
**Estimated Time:** 1 day  

### Task 3 – Implement Backend Logic
Fetch order history from database.  
**Estimated Time:** .5 day 

### Task 4 – Test Functionality
Ensure orders display correctly and reorder works.  
**Estimated Time:** .5 day

---

## TODO
## User Story 14 – Select Pickup or Delivery

**Title:** Select Pickup or Delivery  
**Priority:** High  
**Total Estimate:** 2 days  

### Task 1 – Add Option in Order Class
Store pickup or delivery preference.  
**Estimated Time:** 1 day  

### Task 2 – Create UI Component
Allow user to choose pickup or delivery.  
**Estimated Time:** .5 day  

### Task 3 – Update Backend Logic
Handle order routing based on choice.  
**Estimated Time:** 1 day  

### Task 4 – Test Selection Functionality
Ensure selection is saved and reflected in orders.  
**Estimated Time:** 1 day  

---

## TODO
## User Story 15 – Order Confirmation Notifications

**Title:** Order Confirmation Notifications  
**Priority:** High  
**Total Estimate:** 2 days  

### Task 1 – Create Notification Class
Handle sending notifications.  
**Estimated Time:** 1 day  

### Task 2 – Integrate with Order System
Trigger notification on successful order.  
**Estimated Time:** 1 day  

### Task 3 – Design Notification Templates
Email, SMS, or app push.  
**Estimated Time:** .5 day  

### Task 4 – Test Notifications
Ensure users receive correct confirmations.  
**Estimated Time:** .5 day  