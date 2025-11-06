import { team, stats, processSteps, technologies } from '../data/mockData'
import { Award, Users, Target, Heart } from 'lucide-react'
import './About.css'

const About = () => {
  const values = [
    {
      icon: <Target size={32} />,
      title: 'Innovation',
      description: 'We stay ahead with cutting-edge technologies and innovative solutions'
    },
    {
      icon: <Heart size={32} />,
      title: 'Client Focus',
      description: 'Your success is our priority. We build lasting partnerships'
    },
    {
      icon: <Award size={32} />,
      title: 'Excellence',
      description: 'We deliver quality work that exceeds expectations'
    },
    {
      icon: <Users size={32} />,
      title: 'Teamwork',
      description: 'Collaboration and expertise drive our success'
    }
  ]

  return (
    <main>
      <section className="about-hero">
        <div className="container">
          <div className="about-hero-content">
            <h1 className="page-title">About Digital Creators</h1>
            <p className="page-subtitle">
              Empowering businesses with complete IT solutions since 2013
            </p>
          </div>
        </div>
      </section>

      <section className="section about-story">
        <div className="container">
          <div className="about-content">
            <div className="about-text">
              <h2>Our Story</h2>
              <p>
                Digital Creators was founded with a vision to provide comprehensive 
                IT solutions that help businesses thrive in the digital age. What started 
                as a small team of passionate developers has grown into a full-service 
                IT company serving clients worldwide.
              </p>
              <p>
                We specialize in web development, digital marketing, cloud hosting, 
                mobile development, and IT consulting. Our team combines technical 
                expertise with creative solutions to deliver results that matter.
              </p>
              <p>
                Over the years, we've completed hundreds of projects, helping businesses 
                of all sizes achieve their digital transformation goals. We're proud of 
                our track record and the lasting relationships we've built with our clients.
              </p>
            </div>
            <div className="about-stats">
              {stats.map((stat, index) => (
                <div key={index} className="about-stat">
                  <div className="about-stat-number">{stat.number}</div>
                  <div className="about-stat-label">{stat.label}</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      <section className="section values-section">
        <div className="container">
          <div className="section-header">
            <h2 className="section-title">Our Values</h2>
            <p className="section-description">
              The principles that guide everything we do
            </p>
          </div>
          <div className="values-grid">
            {values.map((value, index) => (
              <div key={index} className="value-card">
                <div className="value-icon">{value.icon}</div>
                <h3 className="value-title">{value.title}</h3>
                <p className="value-description">{value.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="section process-section">
        <div className="container">
          <div className="section-header">
            <h2 className="section-title">How We Work</h2>
            <p className="section-description">
              Our proven process ensures successful project delivery
            </p>
          </div>
          <div className="process-steps">
            {processSteps.map(step => (
              <div key={step.id} className="process-step">
                <div className="process-step-number">{step.id}</div>
                <div className="process-step-icon">{step.icon}</div>
                <h3 className="process-step-title">{step.title}</h3>
                <p className="process-step-description">{step.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="section tech-stack-section">
        <div className="container">
          <div className="section-header">
            <h2 className="section-title">Our Technology Stack</h2>
            <p className="section-description">
              We work with the latest and most reliable technologies
            </p>
          </div>
          <div className="tech-categories">
            {['Frontend', 'Backend', 'Database', 'Cloud', 'Mobile', 'DevOps', 'Language', 'API'].map(category => {
              const categoryTechs = technologies.filter(t => t.category === category)
              if (categoryTechs.length === 0) return null
              return (
                <div key={category} className="tech-category">
                  <h3 className="tech-category-title">{category}</h3>
                  <div className="tech-items">
                    {categoryTechs.map((tech, index) => (
                      <div key={index} className="tech-item">
                        <span className="tech-icon">{tech.icon}</span>
                        <span className="tech-name">{tech.name}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      </section>

      <section className="section team-section">
        <div className="container">
          <div className="section-header">
            <h2 className="section-title">Our Team</h2>
            <p className="section-description">
              Meet the experts behind Digital Creators
            </p>
          </div>
          <div className="team-grid">
            {team.map(member => (
              <div key={member.id} className="team-card">
                <img 
                  src={member.image} 
                  alt={member.name}
                  className="team-image"
                />
                <h3 className="team-name">{member.name}</h3>
                <p className="team-role">{member.role}</p>
                <p className="team-bio">{member.bio}</p>
              </div>
            ))}
          </div>
        </div>
      </section>
    </main>
  )
}

export default About

