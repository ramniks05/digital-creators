import { Mail, Phone, Calendar } from 'lucide-react'
import './AdminPages.css'

const AdminMessages = () => {
  // Mock messages - will be fetched from Supabase later
  const messages = [
    {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
      phone: '+1 (555) 123-4567',
      service: 'Web Development',
      message: 'I am interested in developing a new website for my business.',
      date: '2024-01-15'
    },
    {
      id: 2,
      name: 'Jane Smith',
      email: 'jane@example.com',
      phone: '+1 (555) 987-6543',
      service: 'Digital Marketing',
      message: 'Looking for help with our social media marketing campaign.',
      date: '2024-01-14'
    }
  ]

  return (
    <div className="admin-page">
      <div className="admin-page-header">
        <h2>Contact Messages</h2>
      </div>

      <div className="admin-grid">
        {messages.map(message => (
          <div key={message.id} className="admin-card">
            <div className="admin-card-content">
              <h3>{message.name}</h3>
              <div className="message-info">
                <div className="info-item">
                  <Mail size={16} />
                  <span>{message.email}</span>
                </div>
                <div className="info-item">
                  <Phone size={16} />
                  <span>{message.phone}</span>
                </div>
                <div className="info-item">
                  <Calendar size={16} />
                  <span>{message.date}</span>
                </div>
              </div>
              <div className="message-service">
                <strong>Service:</strong> {message.service}
              </div>
              <p className="message-text">{message.message}</p>
              <div className="admin-actions">
                <button className="btn btn-primary">Reply</button>
                <button className="action-btn delete">Archive</button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {messages.length === 0 && (
        <div className="admin-empty">
          <p>No messages yet. Messages from the contact form will appear here.</p>
        </div>
      )}
    </div>
  )
}

export default AdminMessages

