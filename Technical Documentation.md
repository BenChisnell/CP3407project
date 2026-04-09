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
