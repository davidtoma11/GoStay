# GoStay – Premium Web Booking Platform

**GoStay** is a sophisticated marketplace platform designed to connect travelers with exclusive property listings worldwide. The project emphasizes a seamless user experience, modular architecture, and high-security standards.

---

## Key Features

### Authentication & Security
* **Dual Auth System**: Secure login and registration powered by AJAX (Fetch API).
* **Code Verification**: Two-step registration process featuring activation code simulation.
* **Password Recovery**: Complete account recovery flow with secure reset tokens.
* **Security Layer**: Integrated CSRF protection and rigorous backend data validation.

### Search & Advanced Filtering
* **Intelligent Search**: Real-time filtering based on destination, guest count, and availability dates.
* **Amenities Engine**: Advanced filtering system (Wi-Fi, AC, Pool, Pet Friendly, etc.) utilizing optimized SQL LEFT JOIN operations.
* **Featured Destinations**: Interactive landing page cards that dynamically populate search parameters.

### Weather Integration
* **16-Day Forecast**: Integration with the Open-Meteo API to provide localized weather data for search destinations.
* **Geocoding Engine**: Automatic transformation of city names (e.g., "Paris, FRA") into precise GPS coordinates.
* **Dynamic Weather UI**: Custom calendar-style widget with dynamic iconography and current-day highlighting.

### Modular Architecture
* **Reusable Components**: Centralized global footer managed through PHP inclusion logic.
* **Clean URL Structure**: Logical script organization into specialized directories (Auth, Pages, Utils).
* **Responsive Design**: Fully adaptive interface optimized for mobile, tablet, and high-resolution desktop displays.

---

## Technical Stack

| Layer | Technologies |
| :--- | :--- |
| **Frontend** | HTML5, CSS3 (Flexbox/Grid), JavaScript (ES6+), FontAwesome 6 |
| **Backend** | PHP 8.2 (Modular Logic), PDO (MySQL), JSON API |
| **Database** | MySQL (Relational: Cities -> Rooms -> Facilities) |
| **APIs** | Open-Meteo (Weather Data), Google ReCaptcha (Security) |

---

## Database Entities

> **Architecture Overview**: The database is structured to maintain high data integrity and support complex filtering.

* **Cities**: Stores global destinations and associated metadata.
* **Rooms**: Detailed property inventory including capacity and pricing.
* **Facilities**: 1:1 mapping for property amenities enabling Boolean-based filtering.
* **Users**: Role-based access control supporting Clients, Managers, and Admins.
* **Reservations**: Core transactional entity with built-in availability validation logic.

---

## File Structure

```text
source/
├── assets/                 # Static resources
│   ├── fonts/              # Custom typography (e.g., Croatah)
│   ├── img/                # Logos, system icons, and placeholders
│   └── uploads/            # Dynamic user-uploaded content
│       ├── cities/         # Representative city imagery
│       └── rooms/          # Property and room photography
│
├── scripts/                # Backend logic and page controllers
│   ├── auth/               # Authentication module
│   │   ├── login.php       # JSON API for login processing
│   │   ├── signup.php      # Registration and verification logic
│   │   ├── logout.php      # Session destruction
│   │   └── reset_confirm.php 
│   │
│   ├── config/             # System configuration
│   │   └── database.php    # PDO MySQL connection class
│   │
│   ├── pages/              # User interface controllers
│   │   ├── index.php       # Authentication entry point
│   │   ├── home.php        # Primary user dashboard
│   │   └── search_results.php 
│   │
│   ├── support/            # Assistance module
│   │   └── contact.php     # Help center and inquiry form
│   │
│   └── utils/              # Helper functions and global components
│       ├── includes/       
│       │   └── footer.php  # Centralized footer component
│       └── weather_logic.php 
│
└── styles/                 # Cascading Style Sheets
    ├── footer.css          # Global footer styling
    ├── home.css            # Landing page design
    ├── login.css           # Authentication panel aesthetics
    ├── login.js            # Frontend logic for Auth (Fetch API)
    └── search_results.css  # Grid, card, and weather widget styles