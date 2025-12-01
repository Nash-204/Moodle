# Gamification Block Web Services API

## Overview
The Gamification Block provides RESTful web services to retrieve user badges and course information from Moodle. These APIs allow external systems to integrate with Moodle's gamification data.

## API Endpoints

### Base URL
All API endpoints are accessible via Moodle's Web Services:
```
https://yourmoodle.com/webservice/rest/server.php
```

### 1. Get User Badges by Email
Retrieves all badges earned by a user, including both local gamification badges and Moodle core badges.

**Method:** `block_gamification_get_user_badges`

**Parameters:**
- `email` (string, required): User's email address

**Sample Request (PHP):**
```php
<?php
$token = 'YOUR_WEB_SERVICE_TOKEN';
$domainname = 'https://yourmoodle.com';
$functionname = 'block_gamification_get_user_badges';

$params = ['email' => 'student@example.com'];
$serverurl = $domainname . '/webservice/rest/server.php' . '?wstoken=' . $token . '&wsfunction=' . $functionname;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $serverurl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
curl_close($ch);
$result = json_decode($response, true);
print_r($result);
?>
```

**Sample Response:**
```json
{
    "success": true,
    "email_hash": "a1b2c3d4e5f6...",
    "userid": 12345,
    "fullname": "John Doe",
    "total_badges": 5,
    "badges": [
        {
            "badgecode": "Leaderboard_Month",
            "name": "Monthly Leader",
            "description": "Awarded for being top of monthly leaderboard",
            "image": "https://yourmoodle.com/blocks/gamification/pix/monthly_leader.png",
            "timeearned": 1672531200,
            "date_earned": "2023-01-01",
            "is_moodle_badge": false
        }
    ]
}
```

### 2. Get User Course Information by Email
Retrieves user's enrolled courses with progress, activity completion, and scores.

**Method:** `block_gamification_get_user_course_info`

**Parameters:**
- `email` (string, required): User's email address

**Sample Request (PHP):**
```php
<?php
$token = 'YOUR_WEB_SERVICE_TOKEN';
$domainname = 'https://yourmoodle.com';
$functionname = 'block_gamification_get_user_course_info';

$params = ['email' => 'student@example.com'];
$serverurl = $domainname . '/webservice/rest/server.php' . '?wstoken=' . $token . '&wsfunction=' . $functionname;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $serverurl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
curl_close($ch);
$result = json_decode($response, true);
print_r($result);
?>
```

**Sample Response:**
```json
{
    "success": true,
    "email_hash": "a1b2c3d4e5f6...",
    "userid": 12345,
    "fullname": "John Doe",
    "total_courses": 3,
    "courses": [
        {
            "id": 101,
            "fullname": "Introduction to Mathematics",
            "shortname": "MATH101",
            "lastaccess": 1672531200,
            "progress": 75,
            "completed_activities": [
                {
                    "name": "Algebra Quiz",
                    "type": "quiz",
                    "score": 85.0,
                    "max_score": 100.0,
                    "percentage": 85.0,
                    "completed_date": 1672531200
                }
            ]
        }
    ]
}
```

## JavaScript Example
```javascript
async function getUserBadges(email) {
    const token = 'YOUR_WEB_SERVICE_TOKEN';
    const domain = 'https://yourmoodle.com';
    
    try {
        const response = await fetch(`${domain}/webservice/rest/server.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                wstoken: token,
                wsfunction: 'block_gamification_get_user_badges',
                email: email
            })
        });
        
        const data = await response.json();
        if (data.success) {
            return data;
        } else {
            console.error('Error:', data.message);
            return null;
        }
    } catch (error) {
        console.error('API call failed:', error);
        return null;
    }
}

// Usage
getUserBadges('student@example.com').then(data => {
    if (data) {
        data.badges.forEach(badge => {
            console.log(`Badge: ${badge.name}, Earned: ${badge.date_earned}`);
        });
    }
});
```
## React JS Example Badge
```javascript
import React, { useState } from 'react';

const MoodleBadges = () => {
  const [email, setEmail] = useState("");
  const [badges, setBadges] = useState([]);
  const [loading, setLoading] = useState(false);

  const getUserBadges = async () => {
    setLoading(true);

    const token = "YOUR_WEB_SERVICE_TOKEN";  
    const domain = "https://yourmoodle.com";

    try {
      const response = await fetch(`${domain}/webservice/rest/server.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
          wstoken: token,
          wsfunction: "block_gamification_get_user_badges",
          email: email,
          moodlewsrestformat: "json"
        }),
      });

      const data = await response.json();

      if (data.success) {
        setBadges(data.badges);
      } else {
        alert("Error: " + data.message);
      }
    } catch (error) {
      console.error("API error:", error);
      alert("API call failed");
    }

    setLoading(false);
  };

  return (
    <div style={{ padding: "20px" }}>
      <h2>Moodle Gamification: User Badges</h2>

      <input
        type="email"
        placeholder="Enter user email"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        style={{ padding: "8px", width: "250px", marginRight: "10px" }}
      />

      <button onClick={getUserBadges} disabled={loading}>
        {loading ? "Loading..." : "Get Badges"}
      </button>

      <ul>
        {badges.map((badge, index) => (
          <li key={index}>
            <strong>{badge.name}</strong> – Earned: {badge.date_earned}
          </li>
        ))}
      </ul>
    </div>
  );
};

export default MoodleBadges;

```

## React JS Example Course
```javascript
import React, { useState } from 'react';

const MoodleCourses = () => {
  const [email, setEmail] = useState("");
  const [courses, setCourses] = useState([]);
  const [loading, setLoading] = useState(false);

  const getUserCourses = async () => {
    setLoading(true);

    const token = "YOUR_WEB_SERVICE_TOKEN";  
    const domain = "https://yourmoodle.com";

    try {
      const response = await fetch(`${domain}/webservice/rest/server.php`, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          wstoken: token,
          wsfunction: "block_gamification_get_user_course_info",
          email: email,
          moodlewsrestformat: "json"
        }),
      });

      const data = await response.json();

      if (data.success) {
        setCourses(data.courses);
      } else {
        alert("Error: " + data.message);
      }

    } catch (error) {
      console.error("API error:", error);
      alert("API call failed");
    }

    setLoading(false);
  };

  return (
    <div style={{ padding: "20px" }}>
      <h2>Moodle Gamification: User Courses</h2>

      <input
        type="email"
        placeholder="Enter user email"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        style={{ padding: "8px", width: "250px", marginRight: "10px" }}
      />

      <button onClick={getUserCourses} disabled={loading}>
        {loading ? "Loading..." : "Get Courses"}
      </button>

      <div style={{ marginTop: "20px" }}>
        {courses.map((course) => (
          <div key={course.id} style={{ marginBottom: "15px" }}>
            <h3>{course.fullname}</h3>
            <p>Progress: {course.progress}%</p>
            <ul>
              {course.completed_activities.map((act, idx) => (
                <li key={idx}>
                  {act.name} – {act.percentage}%
                </li>
              ))}
            </ul>
          </div>
        ))}
      </div>
    </div>
  );
};

export default MoodleCourses;

```


## Setup Instructions

### 1. Enable Web Services
1. Go to `Site administration > Advanced features`
2. Enable **Web services**
3. Enable **Protocols > REST protocol**

### 2. Create External Service
1. Go to `Site administration > Plugins > Web services > External services`
2. Add a new service with:
   - Name: "Gamification Services"
   - Short name: `gamification_services`
   - Enabled: Yes
   - Authorized users only: No (or Yes for better security)

### 3. Add Functions to Service
1. Edit the "Gamification Services"
2. Add these functions:
   - `block_gamification_get_user_badges`
   - `block_gamification_get_user_course_info`

### 4. Create Token
1. Go to `Site administration > Plugins > Web services > Manage tokens`
2. Create a new token:
   - User: Select a user with appropriate permissions
   - Service: "Gamification Services"
   - IP restriction: (Optional) Limit to specific IPs

## Security Notes
⚠️ **Important Security Considerations:**
1. **Authentication Required**: These APIs currently have `loginrequired => false`. Consider changing to `true` for production use.
2. **Rate Limiting**: Implement rate limiting to prevent abuse.
3. **Input Validation**: Always validate email inputs on the client side.
4. **HTTPS**: Always use HTTPS in production.
5. **Token Security**: Keep web service tokens secure and never expose them in client-side code.

## Error Handling
The APIs return standardized responses:
- **Success**: `{"success": true, ...}`
- **Error**: `{"success": false, "message": "Error description"}`

Common error messages:
- `"User not found"` - Email doesn't exist in the system
- `"Error fetching badges"` - Internal server error
- `"Invalid email address"` - Malformed email parameter

## File Structure
```
blocks/gamification/
├── classes/
│   └── external/
│       ├── badges_external.php    # Badges API implementation
│       └── courses_external.php   # Courses API implementation
├── db/
│   └── services.php               # Service definitions
└── ...
```

## Support
For issues or questions:
1. Check Moodle logs for detailed error information
2. Verify web service configuration
3. Ensure the gamification block is properly installed and configured
4. Confirm user has appropriate permissions and enrollments

## License
This code is part of the Gamification Block for Moodle and follows Moodle's GPL v3 license.