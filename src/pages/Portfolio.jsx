import { useState } from 'react'
import { projects } from '../data/mockData'
import { ExternalLink } from 'lucide-react'
import './Portfolio.css'

const Portfolio = () => {
  const [filter, setFilter] = useState('All')
  const categories = ['All', ...new Set(projects.map(p => p.category))]

  const filteredProjects = filter === 'All' 
    ? projects 
    : projects.filter(p => p.category === filter)

  return (
    <main>
      <section className="portfolio-hero">
        <div className="container">
          <div className="portfolio-hero-content">
            <h1 className="page-title">Our Portfolio</h1>
            <p className="page-subtitle">
              Showcasing our successful projects and client achievements
            </p>
          </div>
        </div>
      </section>

      <section className="section portfolio-section">
        <div className="container">
          <div className="portfolio-filters">
            {categories.map(category => (
              <button
                key={category}
                className={`filter-btn ${filter === category ? 'active' : ''}`}
                onClick={() => setFilter(category)}
              >
                {category}
              </button>
            ))}
          </div>

          <div className="portfolio-grid">
            {filteredProjects.map(project => (
              <div key={project.id} className="portfolio-card">
                <div className="portfolio-image-wrapper">
                  <img 
                    src={project.image} 
                    alt={project.title}
                    className="portfolio-image"
                  />
                  <div className="portfolio-overlay">
                    <ExternalLink size={24} />
                  </div>
                </div>
                <div className="portfolio-content">
                  <div className="portfolio-header">
                    {project.clientLogo && (
                      <div className="portfolio-client-logo">
                        <img 
                          src={project.clientLogo} 
                          alt={project.client || 'Client'}
                          className="portfolio-client-logo-img"
                        />
                      </div>
                    )}
                    <span className="portfolio-category">{project.category}</span>
                  </div>
                  <h3 className="portfolio-title">{project.title}</h3>
                  <p className="portfolio-description">{project.description}</p>
                  {project.client && (
                    <div className="portfolio-client">
                      <strong>Client:</strong> {project.client}
                    </div>
                  )}
                  <div className="portfolio-tech">
                    {project.tech.map((tech, index) => (
                      <span key={index} className="tech-tag">{tech}</span>
                    ))}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>
    </main>
  )
}

export default Portfolio

