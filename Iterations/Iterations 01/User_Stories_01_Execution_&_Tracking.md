### User 1 Alex (M, 28) Priority High, initial estimate 2 days As a User of the app, I want to create an account. So that I can place food orders.

Task 1:
Create account creation page with ui to enter personal information - 1 day

Task 2:
set up back end infrastructure to store account information - 1 day

Total estimate - 2 days
### User 2 Sophie (F, 34) Priority High, estimate 1 day As a User of the app, I want to log in securely. So that my account is protected.

Task 1:
Create log in page with ui to enter email and password - 1 day

Task 2:
Create functionality to accept passwords using encrypted keys - 1 day

Total estimate - 2 days
### User 3 Liam (M, 22) Priority High, initial estimate 2 days As a User of the app, I want to browse nearby restaurants. So that I can see available options.

Task 1:
Create a database of restaurants that have signed up for the delivery service - Half a day

Task 2:
Create functionality to filter restaurants based on proximity to the user's listed address - 1 days

Task 3:
Connect the database to the site and display the list to the user - 1 day

Total estimate - 2.5 days
### User 5 Olivia (F, 30) Priority High, initial estimate 2 days As a User of the app, I want to view restaurant menus. So that I can decide what to order.

Task 1:
Create a database of the available items for each restaurant - 1 day

Task 2:
Connect the database to the site for each restaurant - 1 day

Total estimate - 2 days


# User Story Task Breakdown

---

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