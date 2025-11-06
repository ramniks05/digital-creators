# Digital Creators - IT Solutions Website

A modern, responsive website for Digital Creators, an IT solutions company offering development, digital marketing, hosting, and cloud support services.

## Features

- 🎨 Modern, attractive UI/UX with gradient theme
- 📱 Fully responsive design
- 🚀 React with Vite for fast development
- 📊 Admin panel for content management
- 🎯 Mock data (ready for Supabase integration)
- 🎨 Beautiful color scheme with purple/indigo gradients

## Pages

- **Home**: Hero section, services showcase, testimonials, stats
- **About**: Company story, team, values
- **Services**: Detailed service listings
- **Portfolio**: Project showcase with filtering
- **Contact**: Contact form and information
- **Admin Panel**: Content management dashboard

## Getting Started

### Installation

```bash
npm install
```

### Development

```bash
npm run dev
```

The site will be available at `http://localhost:5173`

### Build

```bash
npm run build
```

## Admin Panel

Access the admin panel at `/admin/login`

**Mock Login**: Enter any email and password to access (will be replaced with Supabase authentication later)

## Tech Stack

- React 18
- React Router DOM
- Vite
- Lucide React (Icons)
- CSS3 with custom properties

## Future Integration

This project is set up to integrate with Supabase for:
- Database management
- Authentication
- File storage
- Real-time updates

## Project Structure

```
src/
├── components/     # Reusable components (Header, Footer)
├── pages/         # Page components
│   └── admin/     # Admin panel pages
├── data/          # Mock data
└── App.jsx        # Main app component
```

## Color Theme

- Primary: Indigo (#6366f1)
- Secondary: Purple (#8b5cf6)
- Accent: Pink (#ec4899)
- Gradient: Indigo → Purple → Pink

## License

MIT

