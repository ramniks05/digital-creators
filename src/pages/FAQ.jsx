import { useState } from 'react'
import { ChevronDown, ChevronUp, HelpCircle } from 'lucide-react'
import { faqs } from '../data/mockData'
import './FAQ.css'

const FAQ = () => {
  const [openFAQ, setOpenFAQ] = useState(null)

  const toggleFAQ = (id) => {
    setOpenFAQ(openFAQ === id ? null : id)
  }

  return (
    <main>
      <section className="faq-hero">
        <div className="container">
          <div className="faq-hero-content">
            <HelpCircle size={64} />
            <h1 className="page-title">Frequently Asked Questions</h1>
            <p className="page-subtitle">
              Find answers to common questions about our services and processes
            </p>
          </div>
        </div>
      </section>

      <section className="section faq-section">
        <div className="container">
          <div className="faq-list">
            {faqs.map(faq => (
              <div 
                key={faq.id} 
                className={`faq-item ${openFAQ === faq.id ? 'open' : ''}`}
              >
                <button 
                  className="faq-question"
                  onClick={() => toggleFAQ(faq.id)}
                >
                  <span>{faq.question}</span>
                  {openFAQ === faq.id ? (
                    <ChevronUp size={24} />
                  ) : (
                    <ChevronDown size={24} />
                  )}
                </button>
                <div className="faq-answer">
                  <p>{faq.answer}</p>
                </div>
              </div>
            ))}
          </div>

          <div className="faq-cta">
            <h3>Still have questions?</h3>
            <p>Can't find the answer you're looking for? Please get in touch with our friendly team.</p>
            <a href="/contact" className="btn btn-primary">
              Contact Us
            </a>
          </div>
        </div>
      </section>
    </main>
  )
}

export default FAQ

