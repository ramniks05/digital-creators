import { Link } from 'react-router-dom'
import { ArrowRight, CheckCircle, Star, ExternalLink } from 'lucide-react'
import { services, testimonials, stats, projects, clients } from '../data/mockData'
import './Home.css'

const Home = () => {
  return (
    <main>
      {/* Hero Section */}
      <section className="hero">
        <div className="hero-background"></div>
        <div className="container">
          <div className="hero-content fade-in">
            <h1 className="hero-title">
              Complete IT Solutions for
              <span className="gradient-text"> Your Business</span>
            </h1>
            <p className="hero-description">
              We provide cutting-edge development, digital marketing, cloud hosting, 
              and comprehensive IT services to help your business thrive in the digital world.
            </p>
            <div className="hero-buttons">
              <Link to="/contact" className="btn btn-primary">
                Get Started <ArrowRight size={20} />
              </Link>
              <Link to="/services" className="btn btn-outline">
                Our Services
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* Stats Section */}
      <section className="stats-section">
        <div className="container">
          <div className="stats-grid">
            {stats.map((stat, index) => (
              <div key={index} className="stat-card">
                <div className="stat-number">{stat.number}</div>
                <div className="stat-label">{stat.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Services Section */}
      <section className="section services-section">
        <div className="container">
          <div className="section-header">
            <h2 className="section-title">Our Services</h2>
            <p className="section-description">
              Comprehensive IT solutions tailored to your business needs
            </p>
          </div>
          <div className="services-grid">
            {services.map(service => (
              <div key={service.id} className="service-card">
                <div className="service-icon">{service.icon}</div>
                <h3 className="service-title">{service.title}</h3>
                <p className="service-description">{service.description}</p>
                <ul className="service-features">
                  {service.features.map((feature, index) => (
                    <li key={index}>
                      <CheckCircle size={16} />
                      {feature}
                    </li>
                  ))}
                </ul>
                <Link to="/services" className="service-link">
                  Learn More <ArrowRight size={16} />
                </Link>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Client Projects Section */}
      <section className="section client-projects-section">
        <div className="container">
          <div className="section-header">
            <h2 className="section-title">Our Client Projects</h2>
            <p className="section-description">
              Showcasing successful projects we've delivered for our clients
            </p>
          </div>
          <div className="client-projects-grid">
            {projects.filter(p => p.featured).slice(0, 3).map(project => (
              <div key={project.id} className="client-project-card">
                <div className="client-project-image-wrapper">
                  <img 
                    src={project.image} 
                    alt={project.title}
                    className="client-project-image"
                  />
                  <div className="client-project-overlay">
                    <ExternalLink size={24} />
                  </div>
                </div>
                <div className="client-project-content">
                  <div className="client-project-header">
                    <div className="client-logo-small">
                      <img 
                        src={project.clientLogo} 
                        alt={project.client}
                        className="client-logo-img"
                      />
                    </div>
                    <span className="client-project-category">{project.category}</span>
                  </div>
                  <h3 className="client-project-title">{project.title}</h3>
                  <p className="client-project-description">{project.description}</p>
                  <div className="client-project-client">
                    <strong>Client:</strong> {project.client}
                  </div>
                  <div className="client-project-tech">
                    {project.tech.map((tech, index) => (
                      <span key={index} className="tech-badge-small">{tech}</span>
                    ))}
                  </div>
                </div>
              </div>
            ))}
          </div>
          <div className="client-projects-cta">
            <Link to="/portfolio" className="btn btn-secondary">
              View All Projects <ArrowRight size={18} />
            </Link>
          </div>
        </div>
      </section>

      {/* Clients Section */}
      <section className="section clients-section">
        <div className="container">
          <div className="section-header">
            <h2 className="section-title">Trusted By Leading Companies</h2>
            <p className="section-description">
              We're proud to work with innovative businesses across various industries
            </p>
          </div>
          <div className="clients-grid">
            {clients.map(client => (
              <div key={client.id} className="client-logo-card">
                <img 
                  src={client.logo} 
                  alt={client.name}
                  className="client-logo"
                />
                <div className="client-info">
                  <div className="client-name">{client.name}</div>
                  <div className="client-industry">{client.industry}</div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Testimonials Section */}
      <section className="section testimonials-section">
        <div className="container">
          <div className="section-header">
            <h2 className="section-title">What Our Clients Say</h2>
            <p className="section-description">
              Trusted by businesses worldwide
            </p>
          </div>
          <div className="testimonials-grid">
            {testimonials.map(testimonial => (
              <div key={testimonial.id} className="testimonial-card">
                <div className="testimonial-rating">
                  {[...Array(testimonial.rating)].map((_, i) => (
                    <Star key={i} size={16} fill="#fbbf24" color="#fbbf24" />
                  ))}
                </div>
                <p className="testimonial-text">"{testimonial.text}"</p>
                <div className="testimonial-author">
                  <img 
                    src={testimonial.image} 
                    alt={testimonial.name}
                    className="testimonial-avatar"
                  />
                  <div>
                    <div className="testimonial-name">{testimonial.name}</div>
                    <div className="testimonial-role">
                      {testimonial.role}, {testimonial.company}
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="cta-section">
        <div className="container">
          <div className="cta-content">
            <h2 className="cta-title">Ready to Transform Your Business?</h2>
            <p className="cta-description">
              Let's discuss how we can help you achieve your digital goals
            </p>
            <Link to="/contact" className="btn btn-primary">
              Contact Us Today
            </Link>
          </div>
        </div>
      </section>
    </main>
  )
}

export default Home

