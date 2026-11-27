# Security Policy

## Supported Versions

| Version | Supported |
|---|---|
| 0.1.x | ✅ |

## Reporting a Vulnerability

If you discover a security vulnerability in this package, please report it responsibly.

**Do NOT open a public GitHub issue for security vulnerabilities.**

Instead, please email the maintainer directly at:

**grdzelog@gmail.com**

Include:

- A description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

You should receive a response within 48 hours. We will work with you to understand the issue and coordinate a fix before any public disclosure.

## Scope

This package is an HTTP client SDK. It does not:

- Store credentials in files (uses `.env` / Laravel config)
- Access databases
- Serve HTTP routes

Security concerns most likely relate to:

- Token handling and caching
- Credential exposure in logs or error messages
- HTTP transport security (TLS, certificate validation)
