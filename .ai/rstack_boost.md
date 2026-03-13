# RStack AI Development Context

This document provides context for AI coding assistants (GitHub Copilot, Claude Sonnet, etc.) working on the RStack platform.

The goal is to maintain architectural consistency and guide AI-assisted development.

---

# Project Overview

RStack is a **self-hosted application deployment platform**.

It allows users to deploy and manage application stacks on their own infrastructure using Docker.

The platform acts as an orchestration layer between the user and Docker hosts.

RStack is conceptually similar to:

- Laravel Forge
- Coolify
- Render
- Fly.io

But focused on **self-hosted infrastructure**.

---

# Technology Stack

RStack is built using:

- Laravel 12
- Laravel Starter Kit
- Livewire
- Volt
- TailwindCSS
- Alpine.js
- Docker
- MySQL
- Redis

Development environment:

- Laravel Herd
- GitHub Copilot
- Claude Sonnet via Laravel Boost

---

# Architecture Overview

RStack has three main layers:

User Interface  
→ RStack Control Panel (Laravel)

Control Panel  
→ Deployment Engine

Deployment Engine  
→ Docker Hosts

Infrastructure flow:

User → RStack Panel → Docker Host → Containers

---

# Core Concepts

## Servers

Servers are machines that run Docker.

Example:

NUC server  
IP: 192.168.x.x

Each server is registered in the RStack panel.

---

## Projects

A project represents a deployed application.

Example:

Laravel app  
Node API  
Static site

Projects belong to:

- a server
- a stack template

---

## Stacks

Stacks define how an application is deployed.

Stacks are templates containing:

Dockerfile  
docker-compose.yml  
nginx configuration

Examples:

Laravel stack  
Node stack  
Python stack  
Static stack

---

# Deployment Flow

When a user creates a project the system should:

1. Create project directory on server
2. Copy stack template
3. Generate environment variables
4. Create database if required
5. Run docker compose
6. Register service port
7. Configure reverse proxy

---

# Folder Structure

Recommended project structure:

app/
Services/
Actions/
Stacks/
Docker/

database/
migrations/

resources/
views/

routes/

---

# Service Layer

Business logic should be placed in Services.

Example:

ProjectService  
DeploymentService  
ServerService  
StackService

Controllers should remain thin.

---

# Stack Templates

Stack templates are stored in:

storage/stacks/

Example structure:

storage/stacks/laravel

Contains:

Dockerfile  
docker-compose.yml  
nginx.conf  
.env.template

---

# Port Allocation

Each deployed project should receive a unique port.

Ports should start at:

8001

Example:

project1 → 8001  
project2 → 8002  
project3 → 8003

Port allocation should be handled by a PortService.

---

# Coding Guidelines

Follow Laravel best practices.

Use:

- Service classes for logic
- Livewire components for UI
- Form requests for validation
- Eloquent models for persistence

Avoid placing logic directly in controllers.

---

# AI Coding Instructions

When generating code:

- Prefer Laravel conventions
- Write clear service classes
- Keep controllers thin
- Follow SOLID principles
- Prefer readability over clever solutions
- Avoid unnecessary abstraction

---

# First Development Goals

Phase 1 (MVP)

Create:

- Servers table
- Projects table
- Stacks table
- Basic dashboard
- Project creation flow
- Stack selection
- Port allocation

Phase 2

Add:

- Deployment engine
- Docker integration
- Git repository deployment

Phase 3

Add:

- Reverse proxy automation
- Monitoring
- Logs
- Resource limits

---

# Naming Conventions

Projects use lowercase slug names.

Example:

my-app

Domains are stored as:

myapp.domain.com

Ports are numeric integers.

---

# Security

Never store SSH keys in plain text.

Use encrypted storage.

Avoid executing raw shell commands without validation.

---

# Development Philosophy

RStack should be:

Simple  
Predictable  
Developer-friendly  
Infrastructure-first

Avoid unnecessary complexity.