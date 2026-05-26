# ApplyAI API

ApplyAI API is the Laravel backend for an AI-powered resume optimization and job application tracking platform.

It allows users to upload resumes, compare them against job descriptions, receive structured AI analysis, generate tailored resume PDFs, and manage job applications through a kanban-style workflow.

This repository contains the backend only. It exposes JSON APIs and PDF responses for a separate frontend application.

## Features

- Token-based authentication with Laravel Sanctum
- Resume PDF upload and text extraction
- Queued AI resume-to-job analysis
- Structured analysis results:
  - match score
  - keywords
  - gaps
  - strengths
  - weaknesses
  - rewritten bullet suggestions
  - cover letter
- Async resume structuring for PDF generation
- Resume PDF generation with multiple templates:
  - `harvard`
  - `modern`
  - `minimal`
- Job application tracking with kanban statuses
- Drag-and-drop application ordering using stable float positions
- User ownership checks for protected resources

## What This Backend Handles

ApplyAI API is responsible for the core business logic of the platform:

- authenticating users
- storing uploaded resumes
- extracting readable resume text
- running background AI analysis jobs
- returning structured analysis data
- preparing structured resume data for PDF generation
- rendering downloadable resume PDFs
- tracking job applications
- protecting user-owned data

The frontend is responsible for the user interface and consumes this API.

## Product Flow

1. A user registers or logs in.
2. The user uploads a resume PDF.
3. The backend extracts text from the resume.
4. The user submits a job description for analysis.
5. The backend queues an AI analysis job.
6. The frontend polls until the analysis is completed.
7. The user reviews the match score, keyword gaps, strengths, weaknesses, rewritten bullets, and cover letter.
8. The user starts resume structuring for PDF generation.
9. The backend prepares and caches structured resume data.
10. The frontend polls until the structured resume is ready.
11. The user generates a PDF using one of the supported templates.
12. The user can create and manage application cards in the kanban board.

## Frontend API Guide

Detailed frontend integration documentation is available in:

```text
FRONTEND_API_GUIDE.md