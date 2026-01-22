# Publishing Package to Packagist

> **Note:** For Russian documentation, see [PUBLISHING.ru.md](PUBLISHING.ru.md)

## Prerequisites

1. Make sure you have an account on [Packagist.org](https://packagist.org)
2. Create a repository on GitHub/GitLab/Bitbucket named `s-files`

## Publishing Steps

### 1. Initialize Git Repository

```bash
cd packages/s-webs/s-files
git init
git add .
git commit -m "Initial commit"
```

### 2. Create GitHub Repository

1. Go to GitHub and create a new repository named `s-files`
2. Add remote:

```bash
git remote add origin https://github.com/s-systems/s-files.git
# or
git remote add origin https://github.com/s-webs/s-files.git
```

3. Push code:

```bash
git push -u origin main
```

### 3. Create Version Tag

```bash
git tag -a v1.0.0 -m "First release"
git push origin v1.0.0
```

### 4. Register on Packagist

1. Go to [Packagist.org](https://packagist.org)
2. Log in to your account
3. Click "Submit" in the top menu
4. Enter your repository URL: `https://github.com/s-systems/s-files`
5. Click "Check" and then "Submit"

### 5. Configure Automatic Updates (Optional)

1. In your repository settings on GitHub, add a Webhook:
   - URL: `https://packagist.org/api/github?username=s-systems`
   - Content type: `application/json`
   - Secret: your Packagist API token

2. Or use GitHub Service:
   - Go to Settings → Integrations → Packagist
   - Add your Packagist username and API token

## Version Updates

For each update:

```bash
# Update version in composer.json
# Create a new tag
git tag -a v1.0.1 -m "Bug fixes"
git push origin v1.0.1
```

Packagist will automatically update the package (if webhook is configured).

## Installation Verification

After publishing, verify installation:

```bash
composer require s-webs/s-files
```

## Important Notes

- Make sure `composer.json` contains correct information
- Version should follow [Semantic Versioning](https://semver.org/)
- README.md should contain up-to-date information
- Make sure all dependencies are correctly specified
