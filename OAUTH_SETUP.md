# OAuth Authentication Setup Guide

This guide explains how to set up and use OAuth authentication with Google and Facebook in your Laravel API.

## Features

- ✅ **Google OAuth Login** - Sign in with Google
- ✅ **Facebook OAuth Login** - Sign in with Facebook
- ✅ **Account Linking** - Link multiple social accounts to one user
- ✅ **Account Unlinking** - Unlink social accounts safely
- ✅ **Password-less Login** - OAuth users can have null passwords
- ✅ **JWT Token Generation** - Automatic JWT token generation for OAuth users
- ✅ **Email Auto-verification** - OAuth users are auto-verified
- ✅ **Existing User Linking** - Auto-link social accounts to existing users by email

## Table of Contents

1. [Google OAuth Setup](#google-oauth-setup)
2. [Facebook OAuth Setup](#facebook-oauth-setup)
3. [API Endpoints](#api-endpoints)
4. [Usage Examples](#usage-examples)
5. [How It Works](#how-it-works)

---

## Google OAuth Setup

### 1. Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Navigate to **APIs & Services** > **Credentials**

### 2. Create OAuth 2.0 Credentials

1. Click **Create Credentials** > **OAuth client ID**
2. Configure the consent screen (if prompted)
3. Choose **Web application**
4. Add the following redirect URI:

    ```
    http://localhost/api/v1/auth/social/google/callback
    ```

    - Replace `http://localhost` with your actual API URL in production

5. Click **Create**
6. Copy the **Client ID** and **Client Secret**

### 3. Configure Environment Variables

Add the following to your `.env` file:

```env
GOOGLE_CLIENT_ID=your-google-client-id-here
GOOGLE_CLIENT_SECRET=your-google-client-secret-here
GOOGLE_REDIRECT_URI=http://localhost/api/v1/auth/social/google/callback
```

---

## Facebook OAuth Setup

### 1. Create Facebook App

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create a new app or select an existing one
3. Add **Facebook Login** product
4. Navigate to **Settings** > **Basic**

### 2. Configure OAuth Settings

1. Copy the **App ID** and **App Secret**
2. Add the following redirect URI in **Facebook Login** > **Settings**:

    ```
    http://localhost/api/v1/auth/social/facebook/callback
    ```

    - Replace `http://localhost` with your actual API URL in production

### 3. Configure Environment Variables

Add the following to your `.env` file:

```env
FACEBOOK_CLIENT_ID=your-facebook-app-id-here
FACEBOOK_CLIENT_SECRET=your-facebook-app-secret-here
FACEBOOK_REDIRECT_URI=http://localhost/api/v1/auth/social/facebook/callback
```

---

## API Endpoints

### Public Endpoints

#### 1. Redirect to OAuth Provider

```http
GET /api/v1/auth/social/{provider}/redirect
```

**Parameters:**

- `provider` (path): `google` or `facebook`

**Response:** Redirects to the OAuth provider's login page

#### 2. Handle OAuth Callback

```http
GET /api/v1/auth/social/{provider}/callback
```

**Parameters:**

- `provider` (path): `google` or `facebook`

**Response:**

```json
{
    "success": true,
    "message": "Login successful via Google",
    "data": {
        "user": {
            "id": "uuid",
            "name": "John Doe",
            "email": "john@example.com",
            "avatar": "https://...",
            "is_active": true,
            "email_verified_at": "2026-03-19T10:00:00Z",
            "created_at": "2026-03-19T10:00:00Z"
        },
        "token": {
            "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
            "token_type": "bearer",
            "expires_in": 3600
        },
        "is_new": false
    }
}
```

### Authenticated Endpoints

#### 3. Link Social Account

```http
POST /api/v1/auth/social/link
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

**Request Body:**

```json
{
    "provider": "google",
    "access_token": "provider-access-token"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Social account linked successfully",
    "data": {
        "id": "uuid",
        "provider_name": "google",
        "provider_id": "123456789",
        "created_at": "2026-03-19T10:00:00Z"
    }
}
```

#### 4. Unlink Social Account

```http
DELETE /api/v1/auth/social/unlink/{provider}
Authorization: Bearer {jwt_token}
```

**Parameters:**

- `provider` (path): `google` or `facebook`

**Response:**

```json
{
    "success": true,
    "message": "Social account unlinked successfully",
    "data": null
}
```

#### 5. Get Linked Accounts

```http
GET /api/v1/auth/social/accounts
Authorization: Bearer {jwt_token}
```

**Response:**

```json
{
    "success": true,
    "message": "Linked accounts retrieved successfully",
    "data": {
        "providers": ["google", "facebook"]
    }
}
```

---

## Usage Examples

### Example 1: Google OAuth Login Flow (Frontend)

```javascript
// 1. Redirect to Google OAuth
const loginWithGoogle = () => {
    window.location.href = "/api/v1/auth/social/google/redirect";
};

// 2. Handle callback (after redirect back)
// This is handled automatically by the API, which returns JWT token
// Frontend receives the token and stores it
const handleOAuthCallback = () => {
    // The API redirects back to your frontend with the token
    // or you can parse the response from the callback URL
};
```

### Example 2: Linking Social Account (Authenticated User)

```javascript
// Link Google account to existing user
const linkGoogleAccount = async (accessToken) => {
    try {
        const response = await fetch("/api/v1/auth/social/link", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${jwtToken}`,
            },
            body: JSON.stringify({
                provider: "google",
                access_token: accessToken,
            }),
        });

        const data = await response.json();
        console.log("Account linked:", data);
    } catch (error) {
        console.error("Linking failed:", error);
    }
};
```

### Example 3: Getting Linked Accounts

```javascript
const getLinkedAccounts = async () => {
    try {
        const response = await fetch("/api/v1/auth/social/accounts", {
            method: "GET",
            headers: {
                Authorization: `Bearer ${jwtToken}`,
            },
        });

        const data = await response.json();
        console.log("Linked providers:", data.data.providers);
    } catch (error) {
        console.error("Failed to get accounts:", error);
    }
};
```

### Example 4: Unlinking Social Account

```javascript
const unlinkSocialAccount = async (provider) => {
    try {
        const response = await fetch(`/api/v1/auth/social/unlink/${provider}`, {
            method: "DELETE",
            headers: {
                Authorization: `Bearer ${jwtToken}`,
            },
        });

        const data = await response.json();
        console.log("Account unlinked:", data);
    } catch (error) {
        console.error("Unlinking failed:", error);
    }
};
```

---

## How It Works

### 1. OAuth Login Flow

```
User clicks "Login with Google"
    ↓
API redirects to Google OAuth page
    ↓
User authorizes the app
    ↓
Google redirects back with authorization code
    ↓
API exchanges code for access token
    ↓
API fetches user data from Google
    ↓
API checks if social account exists:
    ├─ YES → Log in existing user
    └─ NO  → Check if email exists:
               ├─ YES → Link social account to existing user
               └─ NO  → Create new user + social account
    ↓
Generate JWT token
    ↓
Return user data + token
```

### 2. Account Linking Flow

```
Authenticated user wants to link Google account
    ↓
User provides Google access token
    ↓
API fetches user data from Google
    ↓
API validates:
    ├─ User doesn't already have Google linked
    └─ Google account isn't linked to another user
    ↓
Create social account record
    ↓
Return success
```

### 3. Account Unlinking Safety

The system prevents users from locking themselves out:

- Users without password must have at least one social account linked
- Attempting to unlink the last social account without password fails
- Error message: "Tidak bisa memutus koneksi social account. Anda harus memiliki password atau minimal satu social account aktif."

### 4. Database Schema

#### `social_accounts` table:

- `id` (UUID) - Primary key
- `user_id` (UUID) - Foreign key to users
- `provider_name` (string) - "google" or "facebook"
- `provider_id` (string) - ID from OAuth provider
- `provider_data` (JSON) - Additional provider data (name, avatar, etc.)
- `created_at`, `updated_at` - Timestamps

#### `users` table updates:

- `password` can be `null` for OAuth-only users
- `email_verified_at` is auto-set for OAuth users

---

## Security Features

1. **Stateless OAuth** - Uses `stateless()` to avoid session dependencies
2. **Rate Limiting** - OAuth endpoints are rate-limited
3. **CSRF Protection** - Validates OAuth state parameter
4. **Account Locking Prevention** - Prevents users from unlinking all authentication methods
5. **Unique Constraints** - Prevents duplicate social account links
6. **Foreign Key Cascades** - Social accounts are deleted when user is deleted

---

## Testing

To test OAuth in development:

1. Set up Google OAuth credentials
2. Configure `.env` variables
3. Visit: `http://localhost/api/v1/auth/social/google/redirect`
4. Complete Google login flow
5. You'll be redirected back with JWT token

---

## Troubleshooting

### Issue: "Invalid redirect_uri"

- **Solution:** Ensure the redirect URI in Google Console matches your `.env` `GOOGLE_REDIRECT_URI`

### Issue: "Provider not found"

- **Solution:** Run `composer require laravel/socialite` and check provider configuration

### Issue: "Akun dinonaktifkan" (Account deactivated)

- **Solution:** Check if user `is_active` flag is `true` in the database

### Issue: Cannot unlink social account

- **Solution:** Ensure user has either a password set or at least one other social account linked

---

## Next Steps

1. ✅ Complete Google OAuth setup
2. ✅ Test login flow
3. ✅ Implement account linking in your frontend
4. ✅ Add error handling for OAuth failures
5. ✅ Set up Facebook OAuth (if needed)
6. ✅ Add UI for managing linked accounts

---

## Support

For issues or questions, please refer to:

- [Laravel Socialite Documentation](https://laravel.com/docs/socialite)
- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Facebook Login Documentation](https://developers.facebook.com/docs/facebook-login)
