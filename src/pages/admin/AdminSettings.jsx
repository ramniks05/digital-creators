import { Save } from 'lucide-react'
import './AdminPages.css'

const AdminSettings = () => {
  return (
    <div className="admin-page">
      <div className="admin-page-header">
        <h2>Settings</h2>
      </div>

      <div className="admin-settings">
        <div className="settings-section">
          <h3>General Settings</h3>
          <div className="settings-form">
            <div className="form-group">
              <label>Company Name</label>
              <input type="text" defaultValue="Digital Creators" />
            </div>
            <div className="form-group">
              <label>Email</label>
              <input type="email" defaultValue="info@digitalcreators.com" />
            </div>
            <div className="form-group">
              <label>Phone</label>
              <input type="tel" defaultValue="+1 (555) 123-4567" />
            </div>
            <div className="form-group">
              <label>Address</label>
              <textarea defaultValue="123 Tech Street, Digital City, DC 12345" rows="3"></textarea>
            </div>
            <button className="btn btn-primary">
              <Save size={18} />
              Save Changes
            </button>
          </div>
        </div>

        <div className="settings-section">
          <h3>Supabase Integration</h3>
          <div className="settings-info">
            <p>
              Supabase integration will be implemented later. This will allow you to:
            </p>
            <ul>
              <li>Manage all content through the admin panel</li>
              <li>Store and retrieve data from Supabase database</li>
              <li>Handle user authentication</li>
              <li>Manage file uploads</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  )
}

export default AdminSettings

