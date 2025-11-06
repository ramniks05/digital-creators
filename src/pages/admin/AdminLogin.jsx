import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Lock, Mail } from 'lucide-react'
import './AdminLogin.css'

const AdminLogin = () => {
  const [credentials, setCredentials] = useState({ email: '', password: '' })
  const navigate = useNavigate()

  const handleSubmit = (e) => {
    e.preventDefault()
    // Mock authentication - will integrate with Supabase later
    if (credentials.email && credentials.password) {
      localStorage.setItem('adminAuth', 'true')
      navigate('/admin')
    } else {
      alert('Please enter email and password')
    }
  }

  return (
    <div className="admin-login">
      <div className="admin-login-container">
        <div className="admin-login-card">
          <div className="admin-login-header">
            <h1>Admin Login</h1>
            <p>Digital Creators Admin Panel</p>
          </div>
          <form onSubmit={handleSubmit} className="admin-login-form">
            <div className="form-group">
              <label htmlFor="email">
                <Mail size={18} />
                Email Address
              </label>
              <input
                type="email"
                id="email"
                value={credentials.email}
                onChange={(e) => setCredentials({ ...credentials, email: e.target.value })}
                placeholder="admin@digitalcreators.com"
                required
              />
            </div>
            <div className="form-group">
              <label htmlFor="password">
                <Lock size={18} />
                Password
              </label>
              <input
                type="password"
                id="password"
                value={credentials.password}
                onChange={(e) => setCredentials({ ...credentials, password: e.target.value })}
                placeholder="Enter your password"
                required
              />
            </div>
            <button type="submit" className="btn btn-primary admin-login-btn">
              Sign In
            </button>
          </form>
          <p className="admin-login-note">
            Note: This is a mock login. Real authentication will be implemented with Supabase.
          </p>
        </div>
      </div>
    </div>
  )
}

export default AdminLogin

