# AI Chatbot Platform for Websites

A low-cost AI chatbot solution that allows websites to integrate an intelligent chat assistant trained on their content.

## Features

- 🤖 AI-powered chatbot trained on your website content
- 📊 User-friendly dashboard for analytics and model training
- 👨‍💼 Admin panel for user and subscription management
- 💰 Tiered pricing plans with usage limits
- 🔄 Automatic content updates via sitemap
- 💻 Easy integration with a simple JavaScript snippet
- 🎨 Customizable chatbot appearance
- 📱 Responsive design for all devices

## Requirements

- PHP 8.1 or higher
- MySQL 5.7 or higher
- Redis server
- OpenAI API key
- Composer

## Installation

1. Clone the repository:
```bash
git clone https://github.com/emreires/chatbot-for-websites-ai-startup.git
cd ai-chatbot-platform
```

2. Install dependencies:
```bash
composer install
```

3. Copy the environment file and configure it:
```bash
cp .env.example .env
```

Edit the `.env` file with your:
- Database credentials
- OpenAI API key
- Redis configuration
- Other settings

4. Create the database and tables:
```bash
mysql -u your_username -p your_database_name < database/schema.sql
```

5. Set up your web server (Apache/Nginx) to point to the `public` directory

6. Ensure proper permissions:
```bash
chmod -R 755 public/
chmod -R 755 storage/
```

## Usage

### Website Integration

Add the following script to your website:

```html
<script src="https://your-domain.com/assets/js/chatbot.js"></script>
<script>
const chatbot = new AIChatbot({
    websiteId: 'your-website-id',
    apiEndpoint: 'https://your-domain.com/api/chat',
    position: 'bottom-right',
    primaryColor: '#007bff'
});
</script>
```

### Dashboard Access

1. Create an admin account:
```sql
INSERT INTO users (email, password, role) VALUES 
('admin@example.com', 'hashed_password', 'admin');
```

2. Access the dashboard at `https://your-domain.com/dashboard`

## Plans and Pricing

- Small: 1,000 API calls/month
- Medium: 5,000 API calls/month
- Big: 20,000 API calls/month
- Enterprise: 100,000 API calls/month

## Development

### Directory Structure

```
├── public/          # Public files
│   ├── assets/      # CSS, JS, images
│   ├── dashboard/   # Dashboard frontend
│   └── admin/       # Admin panel frontend
├── src/             # PHP source code
│   ├── Controllers/ # Controllers
│   ├── Models/      # Database models
│   └── Services/    # Business logic
├── templates/       # HTML templates
├── database/        # Database schema
└── vendor/         # Composer packages
```

### Adding New Features

1. Create necessary database migrations
2. Add models in `src/Models/`
3. Implement business logic in `src/Services/`
4. Create controllers in `src/Controllers/`
5. Add frontend components as needed

## Security

- All API endpoints require authentication
- Rate limiting implemented
- SQL injection prevention
- XSS protection
- CORS configuration
- API key rotation support

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, create an issue in the repository. 
