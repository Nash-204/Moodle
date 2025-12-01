# Moodle Gamification Block Plugin

A Moodle block plugin designed to increase learner engagement by adding game-like mechanics such as experience points (XP), badges, and leaderboards. This plugin enhances motivation, encourages participation, and supports a competitive yet friendly learning environment.

---

## Overview

The Moodle Gamification Block Plugin transforms the learning experience by offering an XP-driven ranking system, leaderboards, badges, and administrative gamification tools. Students can see their progress and compare it with peers, while admins can configure XP rules, send invitations, and manage rewards.

---

## Features

### 🎮 Core Gamification
- **Experience Points (XP) System**  
  Earn XP through activities such as quiz completions, course participation, or custom rules.

- **Ranking System**  
  Students are ranked based on total XP. Ranks update automatically.

- **Badges Support**  
  - Local badges (handled within the plugin)  
  - Moodle core badge integration  

### 🏆 Leaderboards
- **Real-Time Leaderboard**
- **Monthly Leaderboard**
- **Yearly Leaderboard**

All leaderboards update dynamically based on user XP.

### 🔧 Admin Tools & Enhancements
- **Give or Take User XP (Admin Only)**  
  Adjust XP for rewards, penalties, or corrections.

- **Categorize Quizzes & Courses (Admin Only)**  
  Categorize activities to control XP calculations or enable special rewards.

- **Quiz Email Invites (Admin Only)**  
  Send personalized quiz invitations to individual students or groups.

---

## Requirements

- Moodle **3.9+** (or the version you intend to support)
- PHP version matching Moodle requirements
- Cron enabled (required for leaderboard updates and email invites)

---

## Installation

1. Download or clone the plugin folder.
2. Place it into Moodle’s block directory:  
```

/moodle/blocks/gamification

```
3. Log in as an **administrator**.
4. Navigate to:  
**Site administration → Notifications**  
Moodle will detect the plugin and guide you through installation.
5. Add the block to any dashboard or course page:
- *Turn editing on → Add a block → Gamification*

---

## Configuration

### XP Rules
Configure how users earn XP:
- Per quiz completion  
- Per course completion  
- Per category rules   
- Bonus XP (manual)

### Leaderboard Settings
- Select which leaderboards to display  

### Badge Settings
- Enable/disable local badges  
- Enable Moodle core badge integration  
- Configure badge award criteria  


---

## How XP & Ranking Works

XP is awarded automatically based on your configured rules.  
The ranking system calculates user rank by comparing total earned XP against others in the same course or system-wide (depending on settings).

Example ranking formula:

```

Rank = Position of user based on XP descending

```

Ranks update whenever XP is updated.

---

## Leaderboards

### Types of Leaderboards

| Type        | Description                                          |
|-------------|------------------------------------------------------|
| Real-Time   | Updates instantly when XP changes                    |
| Monthly     | Shows the top users for the current calendar month   |
| Yearly      | Shows the top users for the current year             |

Leaderboards can be displayed in the block or embedded via templates.

---

## Badges

The plugin supports two badge systems:

### Local Badges
- Managed inside the plugin  
- Awarded based on XP thresholds or activity milestones  

### Moodle Core Badges
- Integrates with Moodle’s built-in badge system  
- Allows badge awarding rules through Moodle’s standard interface  

---

## Admin Tools

### Give or Take XP
Admins can manually:
- Reward good performance  
- Penalize inappropriate actions  
- Adjust XP for technical issues  

### Categorize Quizzes & Courses
Create categories to:
- Group related activities  
- Apply category-based XP rules  
- Track leaderboard segments  

### Quiz Email Invites
Admins can:
- Invite students to take quizzes  
- Use customizable templates  
- Automatically include quiz links  
- Send reminders or follow-ups  

---

## Permissions

| Role         | XP Adjust | Leaderboard View | Badge Manage | Email Invites |
|--------------|-----------|------------------|--------------|----------------|
| Admin        | ✔         | ✔                | ✔            | ✔              |
| Teacher      | Optional  | ✔                | Optional     | Optional       |
| Student      | ✘         | ✔                | View only    | ✔ (receive)    |

All permissions can be overridden via:  
**Site administration → Users → Permissions → Define roles**

---


