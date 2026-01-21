import { useState } from 'react'
import { testimonials } from '../../data/mockData'
import { Plus, Edit, Trash2, Star } from 'lucide-react'
import './AdminPages.css'

const AdminTestimonials = () => {
  const [testimonialList, setTestimonialList] = useState(testimonials)
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [editingTestimonial, setEditingTestimonial] = useState(null)

  const handleDelete = (id) => {
    if (window.confirm('Are you sure you want to delete this testimonial?')) {
      setTestimonialList(testimonialList.filter(t => t.id !== id))
    }
  }

  return (
    <div className="admin-page">
      <div className="admin-page-header">
        <h2>Manage Testimonials</h2>
        <button className="btn btn-primary" onClick={() => setIsModalOpen(true)}>
          <Plus size={18} />
          Add New Testimonial
        </button>
      </div>

      <div className="admin-grid">
        {testimonialList.map(testimonial => (
          <div key={testimonial.id} className="admin-card">
            <div className="admin-card-content">
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
              <div className="admin-actions">
                <button 
                  className="action-btn edit"
                  onClick={() => {
                    setEditingTestimonial(testimonial)
                    setIsModalOpen(true)
                  }}
                >
                  <Edit size={16} />
                  Edit
                </button>
                <button 
                  className="action-btn delete"
                  onClick={() => handleDelete(testimonial.id)}
                >
                  <Trash2 size={16} />
                  Delete
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {isModalOpen && (
        <div className="admin-modal">
          <div className="admin-modal-content">
            <h3>{editingTestimonial ? 'Edit Testimonial' : 'Add New Testimonial'}</h3>
            <p className="admin-modal-note">
              Testimonial management will be integrated with Supabase later.
            </p>
            <button 
              className="btn btn-secondary"
              onClick={() => {
                setIsModalOpen(false)
                setEditingTestimonial(null)
              }}
            >
              Close
            </button>
          </div>
        </div>
      )}
    </div>
  )
}

export default AdminTestimonials

