# Testing Guide

DevPanel includes comprehensive testing infrastructure to ensure quality and stability.

## Running Tests Locally

### API Smoke Tests
Test all API endpoints, authentication, and security features:

```bash
DEVPANEL_TEST_PASSWORD=your_password ./scripts/devpanel-api-smoke.sh
```

Coverage:
- Login and authentication
- Dashboard endpoints
- Permissions and roles
- Notifications system
- Users management
- Domains endpoints
- Backups and scheduled backups
- Docker detection
- System stats
- Terminal commands
- Git operations
- File Manager

### Functional Smoke Tests
Test real workflows like backups, templates, and Docker operations:

```bash
DEVPANEL_TEST_PASSWORD=your_password ./scripts/devpanel-functional-smoke.sh
```

Coverage:
- File Manager write operations
- Backup creation and restore
- Template import
- Docker compose operations
- Docker setup assistant

### Extended Functional Tests
Test new marketplace and updater features:

```bash
DEVPANEL_TEST_PASSWORD=your_password ./scripts/devpanel-extended-functional-smoke.sh
```

Coverage:
- Plugin system endpoints
- Marketplace endpoints
- Releases fetching
- Updater endpoints
- New pages (releases, CI health)

### Visual Smoke Tests
Verify UI elements and pages render correctly with Chromium:

```bash
DEVPANEL_TEST_PASSWORD=your_password ./scripts/devpanel-visual-smoke.sh
```

Coverage:
- Dashboard page elements
- Settings page components
- Users page elements
- Projects page elements
- File Manager interface
- Audit page elements
- Changelog page elements
- About page elements
- Doctor page elements
- Installer page elements

### Extended Visual Tests
Test visual rendering of new pages:

```bash
DEVPANEL_TEST_PASSWORD=your_password ./scripts/devpanel-extended-visual-smoke.sh
```

Coverage:
- Releases page elements
- CI Health page elements
- Additional dashboard components

### Run All Local Tests

```bash
DEVPANEL_TEST_PASSWORD=your_password ./scripts/devpanel-local-smoke.sh
```

Executes all tests sequentially:
1. API smoke tests
2. Functional smoke tests
3. Extended functional tests
4. Visual smoke tests
5. Extended visual tests

## GitHub Actions CI

DevPanel uses GitHub Actions for continuous integration.

### Workflows
- **Lint**: PHP linting and code style checks
- **Smoke**: API and functional tests with PHP built-in server
- **Release**: Smoke tests before publishing releases

### Requirements
- PHP 7.4+
- Chromium (for visual tests)
- Git
- curl

## Write Tests with Safety

Optional write tests create and restore real data:

```bash
DEVPANEL_TEST_PASSWORD=your_password DEVPANEL_SMOKE_WRITE=1 ./scripts/devpanel-api-smoke.sh
```

This flag enables:
- Backup creation with real files
- Selective restore operations
- Template import workflows

## Test Environment Variables

| Variable | Purpose | Default |
|----------|---------|---------|
| `DEVPANEL_TEST_PASSWORD` | Login password | Required |
| `DEVPANEL_PORT` | Server port | 80 |
| `DEVPANEL_SMOKE_WRITE` | Enable write tests | 0 |
| `DEVPANEL_REQUIRE_TOKEN_AUTH` | Require API tokens | 1 |
| `CHROMIUM_BIN` | Chromium executable | chromium |

## Before Release

Always run the full test suite before publishing:

```bash
DEVPANEL_TEST_PASSWORD=your_password ./scripts/devpanel-local-smoke.sh
```

Also verify:
1. No uncommitted changes
2. config.php not staged
3. Sensitive data not in screenshots
4. Fresh setup passes doctor checks
5. All pages load correctly in browser

See [README.md](README.md#-release-checklist) for complete release checklist.
