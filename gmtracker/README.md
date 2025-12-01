# GM Tracker – Moodle Training Attendance Module

GM Tracker is a Moodle activity module designed to accurately track user attendance and participation in training sessions—whether conducted **online (Google Meet)** or **on-site**. It provides reliable time-in/time-out tracking, attendance status, host tools, and automated email invitations to ensure smooth training management.

## Overview

GM Tracker allows instructors, hosts, and training coordinators to run online or onsite training sessions while automatically tracking attendance. Participants can time in, time out, and their attendance status is recorded for reporting and compliance.

This module is ideal for:
- Corporate training  
- University or school workshops  
- Compliance and certification sessions  
- Remote or hybrid learning environments  

---

## Features

### 📧 Email Invites  
- Send training invitations directly to users  
- Includes session details, Google Meet link (if online)

### 🌐 Google Meet Online Training  
- Create online training sessions linked with Google Meet  
- Users join via the Meet URL  
- Time-in/out is automatically tracked during the session  

### 🏫 On-Site Training  
- Track physical attendance for in-person training  
- Host can show join and leave code for the trainees   
- Ideal for seminars or company trainings  

### ⏱ User Time Tracking  
- Records user **Time In** and **Time Out**  
- Calculates total participation duration  
- Prevents duplicate or overlapping attendance logs  

### 🧍 User Status  
- Automatically assigns one of the following:  
  - **Complete**  
  - **Incomplete**  

### 👤 Host Features  
Hosts have access to:
- Start/End Session controls  
- View real-time attendee list  
- Users who didnt leeave before the user will be automatically marked incomplete
- Export attendance reports  

---

## Requirements

- Moodle **4.0+** (or your intended support range)  
- Google account (if using Google Meet)  
- Cron running (for auto time-out, Emails, and logs)  

---

## Installation

1. Download or clone the plugin.  
2. Upload the folder into:  
```

/moodle/mod/gmtracker

```
3. Log in as **Administrator**.  
4. Go to **Site administration → Notifications** to complete installation.  
5. Create a new GM Tracker activity in any course.  

---

## Configuration

### General Settings
- Default training time: (seconds)
- Email Sending

### Email Settings  
- Enable/disable Email

---

## How It Works

1. Instructor or Host creates a training session.  
2. Users receive an email invite with session details.  
3. Users join:
- Via Google Meet (online), or  
- By attending onsite  
4. User clicks **Time In**, begins session.  
5. User clicks **Time Out** after completion.  
6. GM Tracker logs attendance and updates status.  

---

## Online Training (Google Meet)

- Users can only join once
- Insert the Meet link inside the activity settings.  
- Users must join via the generated link.  
- Hosts see who is currently active in the Meet.   

---

## On-Site Training

- Users can only join once
- Hosts can monitor who has timed in/out.  
- Supports manual adjustments by the host.  

---

## Host Tools

Hosts can:  
- Start/end session manually  
- Monitor active participants  
- Export attendance logs in CSV/Excel  
- View total attendance hours  

---

## Email Invites

Email invites include:  
- Training title  
- Schedule  
- Location or Google Meet URL  

---

## User Attendance Tracking

For every user, the module records:  
- Time In  
- Time Out  
- Total Time Spent  
- Attendance Status  
- Online/On-Site mode  

---

## Permissions

| Role         | Create Sessions | View Reports | Host Tools | Time In/Out |
|--------------|-----------------|--------------|-------------|--------------|
| Admin        | ✔               | ✔            | ✔           | ✔            |
| Teacher      | ✔               | ✔            | ✔           | ✔            |
| Student      | ✘               | ✘            | ✘           | ✔            |

Permissions can be managed under:  
**Site administration → Users → Permissions → Define roles**

---