# GoStay – Web Booking Platform

**GoStay** is a web-based booking platform (marketplace) that connects **travelers**, **property managers**, and **administrators**.  
It allows users to list, search, and book accommodations easily and securely.

---

## Features
- User registration and authentication  
- Hotel and room management (for managers)  
- Booking system with availability validation  
- Review and messaging system  
- Admin dashboard with full control and analytics  

---

## System Architecture
The app follows a **3-tier architecture** ensuring scalability and clear separation of concerns:

| Layer | Description | Technologies |
|-------|--------------|---------------|
| **Frontend (Presentation)** | User interface | HTML5, CSS3, JavaScript (ES6+) |
| **Backend (Logic)** | Business logic and API | PHP 8+, Apache |
| **Database (Data)** | Data storage and relations | MySQL / MariaDB |

---

## Database Overview
Main entities:
- **users** – authentication and roles (client, manager, admin)  
- **hotels** – listed properties linked to a manager  
- **rooms** – hotel rooms with pricing and photos  
- **reservations** – core entity linking users and rooms  
- **reviews**, **messages**, **analytics** – user feedback, chat, and stats  
