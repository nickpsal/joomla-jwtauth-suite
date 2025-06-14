# Joomla JWT Authentication Suite

This repository provides a complete JWT authentication solution for Joomla 4 & 5, consisting of:
- ✅ **Component**: `com_jwtauth` — Provides a backend interface for token configuration and management.
- ✅ **API Authenticator Plugin**: `jwtlegacy` — Validates JWT tokens on all API requests.
- ✅ **Webservices Plugin**: `jwtcustom` — Provides a custom webservice endpoint for legacy support.
- ✅ **Library**: `lib_jwtauth` — Shared JWT utility functions used by the plugins and component.

---

## ⚙️ Requirements

- Joomla 4.x or Joomla 5.x
- PHP 8.1+

---

## 🚀 Installation

1. Install each extension in the following order via Joomla Extension Manager:
   1. `lib_jwtauth.zip`
   2. `com_jwtauth.zip`
   3. `jwtlegacy.zip`
   4. `jwtcustom.zip`

2. After installation, activate the plugins:
   - Go to **System → Plugins**
   - Search for **JWT**
   - Enable **JWT Auth** and **JWT Legacy** plugins.

3. Configure the component:
   - Go to **Components → JWT Auth → Options**
   - Set your **Secret Key** (e.g. `my-super-secure-jwt-key`).
   - Save this key securely — you will reuse it below.

4. Configure the API Authenticator plugin:
   - Go to **System → Plugins → JWT Auth**
   - Set the **JWT Secret Key** to match the one in the component.
   - Set **Allowed IP Addresses** (example: `::!,213.239.234.105`).
   - Click **Save & Close**.

---

## 🔑 Usage

- Obtain a JWT token using your preferred method (e.g. through `/api/index.php/v1/token` if you implement a controller).
- Authenticate your API calls by adding the header:

  ```http
  Authorization: Bearer YOUR-JWT-TOKEN
