# Database Documentation
- [1. Setup MYSQL Workbench](#1-setup-mysql-workbench)
- [2. Database Connection](#2-database-connection)
- [3. Tables and Attributes](#3-tables-and-attributes)
  - [User](#user)
  - [User Address](#user-address) 
  - [Address](#address)
  - [Restaurants](#restaurants)
  - [Menu](#menu)
  - [Cart](#cart)
  - [Cart Items](#cart-items)
  - [Orders](#orders)
  - [Order Items](#order-items)
  - [Promo Code](#promo-code)
- [4. Add or Delete Data from Tables](#4-add-or-delete-data-from-tables)


## 1. Setup MYSQL Workbench
Download the [MYSQL Windows Installer](https://dev.mysql.com/downloads/windows/installer/8.0.html) and follow the prompts provided.

Once installed, use the installer to install MYSQL Workbench.

## 2. Database Connection
Create a MySQL Connection with the following details:
- Connection Name: `dbfeedme`
- Connection Method: `Standard TCP/IP`
- Hostname: `ls-fc8a40f81a05efd4711f0d93fb3157bde5480966.cjeoyckasfnr.ap-southeast-2.rds.amazonaws.com`
- Port `3306`
- Username: `dbmasteruser`
- Password: `N2HaS6DBuEcU88YLK0uj`

## 3. Tables and Attributes

### User
| Field Name   | Data Type | Key | Description     |
| ------------ | --------- | --- | --------------- |
| user_id      | INT       | PK  | Unique user ID  |
| first_name   | VARCHAR   |     | User first name |
| last_name    | VARCHAR   |     | User last name  |
| email        | VARCHAR   |     | Login email     |
| password     | VARCHAR   |     | Login password  |
| phone_number | VARCHAR   |     | Contact number  |

### User Address
| Field Name   | Data Type | Key    | Description     |
| ------------ | --------- | ------ | --------------- |
| user_id      | INT       | PK, FK | Unique user ID  |
| address_id   | VARCHAR   | PK, FK | User first name |

### Address
| Field Name      | Data Type | Key | Description     |
| --------------- | --------- | --- | --------------- |
| address_id      | INT       | PK  | Address ID      |
| building_number | VARCHAR   |     | Building Number |
| street_name     | VARCHAR   |     | Street Name     |
| suburb_city     | VARCHAR   |     | Suburb / City   |
| postcode        | INT       |     | Postcode        |

### Restaurants
| Field Name    | Data Type | Key | Description          |
| ------------- | --------- | --- | -------------------- |
| restaurant_id | INT       | PK  | Unique restaurant ID |
| address_id    | INT       | FK  | Unique address ID    |
| name          | VARCHAR   |     | Restaurant name      |
| cuisine_type  | VARCHAR   |     | Cuisine category     |
| rating        | DECIMAL   |     | Average rating       |
| phone_number  | VARCHAR   |     | Contact number       |

### Menu
| Field Name    | Data Type | Key | Description          |
| ------------- | --------- | --- | -------------------- |
| menu_id       | INT       | PK  | Unique menu ID       |
| restaurant_id | INT       | FK  | Unique restaurant ID |
| item_name     | VARCHAR   |     | Name of food         |
| description   | VARCHAR   |     | Item description     |
| price         | DECIMAL   | NN  | Item price           |
| gluten_free   | BOOLEAN   |     | Is it gluten free    |

### Cart
| Field Name | Data Type | Key | Description       |
| ---------- | --------- | --- | ----------------- |
| cart_id    | INT       | PK  | Cart item ID      |
| user_id    | INT       | FK  | Cart reference ID |

### Cart Items
| Field Name   | Data Type | Key | Description       |
| ------------ | --------- | --- | ----------------- |
| cart_item_id | INT       | PK  | Cart item ID      |
| menu_item_id | INT       | FK  | Selected food     |
| card_id      | INT       | FK  | Cart reference ID |
| quantity     | INT       | NN  | Quantity ordered  |

### Orders
| Field Name      | Data Type | Key | Description       |
| --------------- | --------- | --- | ----------------- |
| order_id        | INT       | PK  | Order ID          |
| restaurant_id   | INT       | FK  | Restaurant        |
| user_id         | INT       | FK  | Customer          |
| address_id      | INT       | FK  | Delivery Location |
| promo_id        | INT       | FK  | Unique Promo Code |
| order_type      | VARCHAR   |     | Drop off type     |
| total_price     | DECIMAL   | NN  | Total Cost        |
| order_status    | VARCHAR   |     | Order Status      |
| payment_method  | VARCHAR   | NN  | Payment Method    |

### Order Items
| Field Name    | Data Type | Key     | Description       |
| ------------- | --------- | ------- | ----------------- |
| order_id      | INT       | PK, FK  | Order item ID     |
| menu_item_id  | INT       | PK, FK  | Food item         |
| quantity      | INT       |         | Quantity ordered  |
| price         | DECIMAL   |         | Price at purchase |

### Promo Code
| Field Name          | Data Type | Key | Description         |
| ------------------- | --------- | --- | ------------------- |
| promo_id            | INT       | PK  | Promo Code ID       |
| code                | VARCHAR   | NN  | Promo Code          |
| discount_percentage | INT       | NN  | Discount percentage |
| expiration_date     | DATE      |     | Expiration Date     |

## 4. Add or Delete Data from Tables
1. Click on the arrow next to `mydb` on the left hand side of the screen under the SCHEMAS heading
2. Click on the arrow next to `Tables` 
3. Select the desired table by clicking on the table/calander icon that appears when you hover over the table name.
4. Either edit the table directly at the bottom of the screen or run SQL scripts to modify the data.  