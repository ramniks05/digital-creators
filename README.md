# Digital Creatorss - Company Website

A modern, responsive company website built with React for Digital Creatorss, an India-based software development and digital marketing company.

## Features

- **Modern UI/UX**: Clean, professional design with smooth animations
- **Fully Responsive**: Works seamlessly on desktop, tablet, and mobile devices
- **Multiple Pages**: Home, About Us, Services, Portfolio, and Contact pages
- **React Router**: Client-side routing for smooth navigation
- **Form Validation**: Contact form with client-side validation
- **Portfolio Filtering**: Filter projects by category
- **SEO-Friendly**: Proper meta tags and semantic HTML structure

## Tech Stack

- **React 18** - UI library
- **Vite** - Build tool and dev server
- **React Router** - Client-side routing
- **Tailwind CSS** - Utility-first CSS framework
- **Framer Motion** - Animation library
- **React Icons** - Icon library

## Project Structure

```
├── src/
│   ├── components/
│   │   ├── Navbar.jsx       # Navigation bar component
│   │   ├── Footer.jsx        # Footer component
│   │   └── PortfolioCard.jsx # Reusable portfolio card component
│   ├── pages/
│   │   ├── Home.jsx          # Home page
│   │   ├── About.jsx         # About Us page
│   │   ├── Services.jsx      # Services page
│   │   ├── Portfolio.jsx     # Portfolio/Our Work page
│   │   └── Contact.jsx        # Contact Us page
│   ├── data/
│   │   ├── portfolioData.js  # Portfolio projects data
│   │   ├── servicesData.js    # Services data
│   │   └── testimonialsData.js # Testimonials data
│   ├── App.jsx               # Main app component with routing
│   ├── main.jsx              # Entry point
│   └── index.css             # Global styles
├── index.html                # HTML template
├── package.json              # Dependencies
├── vite.config.js           # Vite configuration
├── tailwind.config.js       # Tailwind CSS configuration
└── postcss.config.js        # PostCSS configuration
```

## Installation

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Start development server:**
   ```bash
   npm run dev
   ```

3. **Build for production:**
   ```bash
   npm run build
   ```

4. **Preview production build:**
   ```bash
   npm run preview
   ```

## Pages Overview

### Home Page
- Hero section with call-to-action buttons
- Services overview cards
- Why Choose Us section
- Testimonials section
- CTA section

### About Us Page
- Company introduction
- Mission and Vision
- Statistics and achievements
- Core values
- Experience and expertise

### Services Page
- Detailed service cards
- In-depth service descriptions
- Feature lists for each service
- CTA section

### Portfolio Page
- Project showcase with filtering
- Category-based filtering (All, Website, Software, Marketing)
- Responsive grid layout (3 columns desktop, 2 tablet, 1 mobile)
- Project cards with images, descriptions, and technologies

### Contact Page
- Contact form with validation
- Contact information display
- Business hours
- Form submission handling

## Customization

### Colors
The color scheme can be customized in `tailwind.config.js`. The current theme uses blue and purple gradients.

### Content
- Update company information in `src/components/Footer.jsx`
- Modify services in `src/data/servicesData.js`
- Update portfolio projects in `src/data/portfolioData.js`
- Change testimonials in `src/data/testimonialsData.js`

### Images
Replace placeholder images with your own. Images are currently using Unsplash URLs. Update the image URLs in:
- Portfolio data (`src/data/portfolioData.js`)
- Page components (Home, About, Services)

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## License

This project is created for Digital Creatorss. All rights reserved.

## Contact

For questions or support, please contact:
- Email: info@digitalcreatorss.com
- Location: India
