import { useState } from 'react'
import { projects } from '../../data/mockData'
import { Plus, Edit, Trash2 } from 'lucide-react'
import './AdminPages.css'

const AdminProjects = () => {
  const [projectList, setProjectList] = useState(projects)
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [editingProject, setEditingProject] = useState(null)

  const handleDelete = (id) => {
    if (window.confirm('Are you sure you want to delete this project?')) {
      setProjectList(projectList.filter(p => p.id !== id))
    }
  }

  return (
    <div className="admin-page">
      <div className="admin-page-header">
        <h2>Manage Projects</h2>
        <button className="btn btn-primary" onClick={() => setIsModalOpen(true)}>
          <Plus size={18} />
          Add New Project
        </button>
      </div>

      <div className="admin-grid">
        {projectList.map(project => (
          <div key={project.id} className="admin-card">
            <img 
              src={project.image} 
              alt={project.title}
              className="admin-card-image"
            />
            <div className="admin-card-content">
              <span className="admin-card-category">{project.category}</span>
              <h3>{project.title}</h3>
              <p>{project.description}</p>
              <div className="admin-card-tech">
                {project.tech.map((tech, index) => (
                  <span key={index} className="tech-badge">{tech}</span>
                ))}
              </div>
              <div className="admin-actions">
                <button 
                  className="action-btn edit"
                  onClick={() => {
                    setEditingProject(project)
                    setIsModalOpen(true)
                  }}
                >
                  <Edit size={16} />
                  Edit
                </button>
                <button 
                  className="action-btn delete"
                  onClick={() => handleDelete(project.id)}
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
            <h3>{editingProject ? 'Edit Project' : 'Add New Project'}</h3>
            <p className="admin-modal-note">
              Project management will be integrated with Supabase later.
            </p>
            <button 
              className="btn btn-secondary"
              onClick={() => {
                setIsModalOpen(false)
                setEditingProject(null)
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

export default AdminProjects

