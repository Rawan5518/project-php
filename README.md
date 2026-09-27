# Adaptive Home-Based Learning Support Platform for Children with Learning Difficulties

 📌 Project Overview
The Adaptive Home-Based Learning Support Platform is a structured digital ecosystem designed to assist parents and empower children facing learning difficulties such as dyslexia, dyscalculia, attention deficits, and memory challenges. 

By combining parental observations with an adaptive diagnostic test, the platform creates a personalized, highly targeted learning path filled with interactive activities and gamified rewards to ensure consistent improvement.

 🚀 Key Features & Goals

 **Parental Assessment & Questionnaire:** Gathers qualitative insights from parents regarding their child's specific struggles.
 **Adaptive Diagnostic Test System:** Dynamically generates a tailored assessment that focuses on the child's primary difficulty areas.
 **Dual-Evaluation Level Calculation:** Merges parent evaluations with diagnostic test results to accurately pinpoint the child's starting level.
 **Personalized & Dynamic Learning Paths:** Delivers daily targeted activities based on the child's weakest areas with dynamic difficulty adjustments and smart repetition.
 **Gamified Reward System:** Keeps children motivated through earned stars, achievement badges, unlockable stickers, and short reward stories.
 **Parental Dashboard & Progress Analytics:** Gives parents clear reports and analytical insights into their child's daily growth and areas of development.
 **Advanced Database Engineering:** Implements robust SQL architectures featuring stored procedures, triggers, views, and transaction controls.

🧠 Adaptive Diagnostic & Leveling System

 🎯 1. Dynamic Diagnostic Test Generation
The diagnostic test adapts to each child based on the preliminary parental questionnaire:
 **Dyscalculia / Math Struggles:** Focuses heavily on counting and number recognition.
 **Attention Deficits:** Incorporates short spot-the-difference and object-tracking tasks.
 **Memory Challenges:** Uses sequence recall and pattern memory activities.
 **Multiple Difficulties:** Distributes test items proportionally based on severity:
    **Primary Difficulty:** ~50% of questions (e.g., 5/10)
    **Secondary Difficulty:** ~30% of questions (e.g., 3/10)
    **Minor Difficulty / General Skills:** ~20% of questions (e.g., 2/10)

 📊 2. Final Level Determination Formula
The overall baseline level "Final_Level" is computed by blending the parental score and the child's diagnostic test performance:

Final_Level = round((Parent_Level + Child_Level) / 2)

*For children with multiple difficulty areas, each category is scored independently, prioritizing the weakest domain in the daily learning schedule.*

 🎮 Activities & Reward Engine

 Activity Types
 Color & Letter Recognition
 Counting & Basic Numeracy
 Matching & Sorting
 Memory Recall Exercises
 Attention & Focus Games

Motivation System
 ⭐ Stars: Awarded for correct activity answers.
 🏅 Badges: Earned for streaks (e.g., *"5 Correct Answers in a Row"*).
 🎨 Unlockable Content: Stickers and short interactive reward stories.

 🗄️ Database Architecture

The system database manages learning models, attempts, analytics, and dynamic rules using the following core entities:

 `parents` – Parent profiles and login credentials.
 `children` – Child profiles and demographic details.
 `parent_questions` & `parent_assessment` – Questionnaire items and parent assessment scores.
 `diag_questions` & `diag_mapping` – Diagnostic item bank mapped to specific difficulty areas.
 `diag_attempts` – Historical diagnostic attempts and scores.
 `child_weaknesses` – Tracked areas of weakness for dynamic activity targeting.
 `activities` & `child_attempts` – Activity bank, difficulty tiers, and completion logs.
 `learning_rules` – Logic mapping rules for automatic progression and repetition.
 `child_rewards` – Earned stars, badges, and unlocked items.
 `analytics` – Consolidated performance reports for the parent dashboard.

💻 Tech Stack
Frontend:HTML5, CSS3, JavaScript
Backend:PHP
Database:MySQL / MariaDB (utilizing advanced SQL procedures, triggers, and views)
