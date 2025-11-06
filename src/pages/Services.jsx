import { services } from '../data/mockData'
import { CheckCircle, ArrowRight } from 'lucide-react'
import { Link } from 'react-router-dom'
import './Services.css'

const Services = () => {
  return (
    <main>
      <section className="services-hero">
        <div className="container">
          <div className="services-hero-content">
            <h1 className="page-title">Our Services</h1>
            <p className="page-subtitle">
              Complete IT solutions tailored to your business needs
            </p>
          </div>
        </div>
      </section>

      <section className="section services-detail">
        <div className="container">
          <div className="services-list">
            {services.map(service => (
              <div key={service.id} className="service-detail-card">
                <div className="service-detail-header">
                  <div className="service-detail-icon">{service.icon}</div>
                  <h2 className="service-detail-title">{service.title}</h2>
                </div>
                <p className="service-detail-description">{service.description}</p>
                <div className="service-detail-features">
                  <h3>Key Features:</h3>
                  <ul>
                    {service.features.map((feature, index) => (
                      <li key={index}>
                        <CheckCircle size={18} />
                        <span>{feature}</span>
                      </li>
                    ))}
                  </ul>
                </div>
                <Link to="/contact" className="btn btn-primary service-cta">
                  Get Started <ArrowRight size={18} />
                </Link>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="section cta-section">
        <div className="container">
          <div className="cta-content">
            <h2 className="cta-title">Need a Custom Solution?</h2>
            <p className="cta-description">
              We can create a tailored package that fits your specific requirements
            </p>
            <Link to="/contact" className="btn btn-outline">
              Contact Us
            </Link>
          </div>
        </div>
      </section>
    </main>
  )
}

export default Services

