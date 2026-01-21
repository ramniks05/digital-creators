import { useState } from 'react'
import { services } from '../../data/mockData'
import { Plus, Edit, Trash2 } from 'lucide-react'
import './AdminPages.css'

const AdminServices = () => {
  const [serviceList, setServiceList] = useState(services)
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [editingService, setEditingService] = useState(null)

  const handleDelete = (id) => {
    if (window.confirm('Are you sure you want to delete this service?')) {
      setServiceList(serviceList.filter(s => s.id !== id))
    }
  }

  return (
    <div className="admin-page">
      <div className="admin-page-header">
        <h2>Manage Services</h2>
        <button className="btn btn-primary" onClick={() => setIsModalOpen(true)}>
          <Plus size={18} />
          Add New Service
        </button>
      </div>

      <div className="admin-table-container">
        <table className="admin-table">
          <thead>
            <tr>
              <th>Icon</th>
              <th>Title</th>
              <th>Description</th>
              <th>Features</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {serviceList.map(service => (
              <tr key={service.id}>
                <td><span className="service-icon-cell">{service.icon}</span></td>
                <td>{service.title}</td>
                <td>{service.description.substring(0, 60)}...</td>
                <td>{service.features.length} features</td>
                <td>
                  <div className="admin-actions">
                    <button 
                      className="action-btn edit"
                      onClick={() => {
                        setEditingService(service)
                        setIsModalOpen(true)
                      }}
                    >
                      <Edit size={16} />
                    </button>
                    <button 
                      className="action-btn delete"
                      onClick={() => handleDelete(service.id)}
                    >
                      <Trash2 size={16} />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {isModalOpen && (
        <div className="admin-modal">
          <div className="admin-modal-content">
            <h3>{editingService ? 'Edit Service' : 'Add New Service'}</h3>
            <p className="admin-modal-note">
              Service management will be integrated with Supabase later.
            </p>
            <button 
              className="btn btn-secondary"
              onClick={() => {
                setIsModalOpen(false)
                setEditingService(null)
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

export default AdminServices

