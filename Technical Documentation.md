Project Title: “FeedMe” Website Platform

1 Introduction

1.1 Purpose
This document provides technical information about the system architecture and codebase used to configure  and run the “FeedMe” Website Platform. It serves as a reference for administrators, developers and future maintainers.


1.2 Scope
The documentation covers the following areas:
•	Cloud infrastructure hosted on Amazon Web Services (AWS)
•	WordPress deployment on AWS Lightsail
•	Amazon RDS database configuration
•	Database design and management via MySQL Workbench
•	Security, backup and maintenance


1.3 Intended Audience
•	System Administrators
•	Database Administrators
•	Developers
•	Maintenance Teams


1.4 Technology Systems Used

Component	                    Technology
Cloud Hosting	                AWS Lightsail
Operating System	            Lunix (Bitnami Stack)
Web Server	                    Apache
Database	                    Amazon RDS (MySQL)
Database Management Tool	    MySQL Workbench
Content Management System(CMS)	WordPress
Programming Language	        PHP, HTML, CSS


1.5 Justification for Systems Used:
•	AWS Lightsail was chosen for its cost-effectiveness and simple implementation. There was no need to configure any additional software after installation, as the WordPress image used has preinstalled webserver software. It also has database configuration options to connect to the Lightsail server instance. 
•	Amazon RDS Allows SQL queries to probe the database for customized user information (previous orders, etc). Also provides scalability, security and automated backup solutions.
•	WordPress easy to configure and operate Content Management System (CMS).
•	MySQL Workbench for database design, creation and administration.



2 Infrastructure Details

2.1 AWS Lightsail Configuration

Configuration item	                            Details
AWS Region	                                Sydney, Zone A
                                            (ap-southeast-2a)
Instance Type	                            General
Webserver CPU Allocation	                2 X vCPU’s
Webserver Memory Allocation	                2GB
Webserver Storage Allocation	            60GB SSD
Networking Type	                            Dual Stack
Static Public IPv4 Address	                SEE SYSTEM ADMINISTRATOR (NOT DISPLAYED AS REPO IS PUBLIC)
Private IPv4 Address	                    SEE SYSTEM ADMINISTRATOR (NOT DISPLAYED AS REPO IS PUBLIC)
Public IPv6 Address	                        SEE SYSTEM ADMINISTRATOR (NOT DISPLAYED AS REPO IS PUBLIC)
Lightsail server admin username	            SEE SYSTEM ADMINISTRATOR (NOT DISPLAYED AS REPO IS PUBLIC)
Lightsail server admin password	            Download SSH key from AWS portal
Default WordPress admin username	        SEE SYSTEM ADMINISTRATOR (NOT DISPLAYED AS REPO IS PUBLIC)
Default WordPress admin password	        SEE SYSTEM ADMINISTRATOR (NOT DISPLAYED AS REPO IS PUBLIC)


Note: For the purpose of this assignment, SSL is not configured or used on Lightsail due to no webhosting domain used. 



2.2 Amazon RDS Configuration

Configuration item	                            Details
AWS Region	                                Sydney, Zone A
                                            (ap-southeast-2a)

Instance Type	                            General
Webserver CPU Allocation	                2 X vCPU’s
Webserver Memory Allocation	                1GB
Webserver Storage Allocation	            40GB SSD
MySQL Database	                            8.4.8
Database Endpoint	                        SEE SYSTEM ADMINISTRATOR (NOT DISPLAYED AS REPO IS PUBLIC)
Database Username	                        SEE SYSTEM ADMINISTRATOR (NOT DISPLAYED AS REPO IS PUBLIC
Database Password	                        SEE SYSTEM ADMINISTRATOR (NOT DISPLAYED AS REPO IS PUBLIC
Port	                                    3306
Network Security	                        

Public mode enabled - anyone with your database username and password can connect to it (Enabled to allow MySQL Workbench access via a local machine)

Public mode disabled - only  LightSail resources in the same Region as your database can connect to it. (Stops access via MySQL Workbench access via a local machine). 
