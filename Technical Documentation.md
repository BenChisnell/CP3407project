# Project Title: “FeedMe” Website Platform  


## 1. Introduction  

### 1.1 Purpose  
This document provides technical information about the system architecture and codebase used to configure and run the “FeedMe” Website Platform. It serves as a reference for administrators, developers, and future maintainers.  

### 1.2 Scope  
The documentation covers the following areas:  
- Cloud infrastructure hosted on Amazon Web Services (AWS)  
- WordPress deployment on AWS Lightsail  
- Amazon RDS database configuration  
- Database design and management via MySQL Workbench  
- Security, backup, and maintenance  

### 1.3 Intended Audience  
- System Administrators  
- Database Administrators  
- Developers  
- Maintenance Teams  

---

### 1.4 Technology Systems Used  

| Component | Technology |
|----------|----------|
| Cloud Hosting | AWS Lightsail |
| Operating System | Linux (Bitnami Stack) |
| Web Server | Apache |
| Database | Amazon RDS (MySQL) |
| Database Management Tool | MySQL Workbench |
| CMS | WordPress |
| Programming Languages | PHP, HTML, CSS |

---

### 1.5 Justification for Systems Used  
- AWS Lightsail was chosen for cost-effectiveness and simple deployment with preinstalled WordPress stack.  
- Amazon RDS enables scalable, secure database access and supports querying user data.  
- WordPress provides an easy-to-use CMS.  
- MySQL Workbench supports database design and administration.  

---

## 2. Infrastructure Details  

### 2.1 AWS Lightsail Configuration  

| Configuration Item | Details |
|------------------|--------|
| AWS Region | Sydney, Zone A (ap-southeast-2a) |
| Instance Type | General |
| CPU | 2 vCPUs |
| Memory | 2GB |
| Storage | 60GB SSD |
| Networking | Dual Stack |
| Public/Private IPs | See System Administrator |
| Admin Credentials | See System Administrator |

> **Note:** SSL is not configured due to no domain being used.

---

### 2.2 Amazon RDS Configuration  

| Configuration Item | Details |
|------------------|--------|
| AWS Region | Sydney, Zone A (ap-southeast-2a) |
| Instance Type | General |
| CPU | 2 vCPUs |
| Memory | 1GB |
| Storage | 40GB SSD |
| Database Version | MySQL 8.4.8 |
| Port | 3306 |
| Credentials | See System Administrator |

**Network Security Modes:**
- Public mode enabled → Allows external connections (e.g., MySQL Workbench)  
- Public mode disabled → Restricts access to Lightsail resources only  

---

### 2.3 Network and Security  

| Port | Protocol | Service | Access Level | Description |
|------|---------|--------|-------------|------------|
| 22 | SSH | Secure Shell | Restricted | Remote administration |
| 80 | HTTP | Web Traffic | Public | Non-secure access |
| 443 | HTTPS | Secure Web | Public | Encrypted SSL/TLS |
| 3306 | MySQL | Database | Restricted | Secure DB access |

---

### 2.4 WordPress Lightsail Configuration  

| Configuration Item | Details |
|------------------|--------|
| WordPress Version | 6.9.4 |
| Admin Access | See System Administrator |

**Plugins Used:**
- **All-in-One WP Migration** – Full backups  
- **Code Snippets** – Run PHP without editing theme files  
- **WP Mail SMTP** – Reliable email delivery  

---

### 2.5 Database Connection Setup (MySQL Workbench)  

**Connection Settings:**
- Connection Method: Standard (TCP/IP)  
- Hostname: See System Administrator  
- Port: See System Administrator  
- Username: See System Administrator  
- Password: Stored securely in Vault  
- Default Schema: Leave blank  

**Steps:**
1. Create new connection  
2. Enter credentials  
3. Store password in Vault  
4. Test connection  
5. Modify database as required  

---

### 2.6 Accessibility Features  

Accessibility Widget by OneTap was implemented with the following configuration:  

- Icon Size: Large  
- Border: Enabled  
- Color: Black  
- Position: Bottom-right  
- Vertical Offset: 20px  
- Horizontal Offset: 20px  