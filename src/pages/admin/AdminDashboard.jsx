import { useEffect, useState } from 'react'
import { useNavigate, Routes, Route, Link, useLocation } from 'react-router-dom'
import { 
  LayoutDashboard, 
  Briefcase, 
  Users, 
  MessageSquare, 
  Settings, 
  LogOut,
  Menu,
  X
} from 'lucide-react'
import AdminServices from './AdminServices'
import AdminProjects from './AdminProjects'
import AdminTestimonials from './AdminTestimonials'
import AdminMessages from './AdminMessages'
import AdminSettings from './AdminSettings'
import './AdminDashboard.css'

const AdminDashboard = () => {
  const navigate = useNavigate()
  const location = useLocation()
  const [isSidebarOpen, setIsSidebarOpen] = useState(false)

  useEffect(() => {
    // Check authentication
    const isAuthenticated = localStorage.getItem('adminAuth')
    if (!isAuthenticated) {
      navigate('/admin/login')
    }
  }, [navigate])

  const handleLogout = () => {
    localStorage.removeItem('adminAuth')
    navigate('/admin/login')
  }

  const menuItems = [
    { path: '/admin', label: 'Dashboard', icon: <LayoutDashboard size={20} /> },
    { path: '/admin/services', label: 'Services', icon: <Briefcase size={20} /> },
    { path: '/admin/projects', label: 'Projects', icon: <Briefcase size={20} /> },
    { path: '/admin/testimonials', label: 'Testimonials', icon: <MessageSquare size={20} /> },
    { path: '/admin/messages', label: 'Messages', icon: <MessageSquare size={20} /> },
    { path: '/admin/settings', label: 'Settings', icon: <Settings size={20} /> }
  ]

  return (
    <div className="admin-dashboard">
      <aside className={`admin-sidebar ${isSidebarOpen ? 'open' : ''}`}>
        <div className="admin-sidebar-header">
          <div className="admin-logo">
            <span className="logo-icon">🚀</span>
            <span className="logo-text">Admin Panel</span>
          </div>
          <button 
            className="sidebar-close"
            onClick={() => setIsSidebarOpen(false)}
          >
            <X size={24} />
          </button>
        </div>
        <nav className="admin-nav">
          {menuItems.map(item => (
            <Link
              key={item.path}
              to={item.path}
              className={`admin-nav-link ${location.pathname === item.path ? 'active' : ''}`}
              onClick={() => setIsSidebarOpen(false)}
            >
              {item.icon}
              <span>{item.label}</span>
            </Link>
          ))}
        </nav>
        <button className="admin-logout-btn" onClick={handleLogout}>
          <LogOut size={20} />
          <span>Logout</span>
        </button>
      </aside>

      <div className="admin-main">
        <header className="admin-header">
          <button 
            className="sidebar-toggle"
            onClick={() => setIsSidebarOpen(true)}
          >
            <Menu size={24} />
          </button>
          <h1 className="admin-page-title">
            {menuItems.find(item => item.path === location.pathname)?.label || 'Dashboard'}
          </h1>
        </header>

        <main className="admin-content">
          <Routes>
            <Route path="/" element={<AdminOverview />} />
            <Route path="/services" element={<AdminServices />} />
            <Route path="/projects" element={<AdminProjects />} />
            <Route path="/testimonials" element={<AdminTestimonials />} />
            <Route path="/messages" element={<AdminMessages />} />
            <Route path="/settings" element={<AdminSettings />} />
          </Routes>
        </main>
      </div>
    </div>
  )
}

const AdminOverview = () => {
  const { stats } = require('../../data/mockData')
  
  return (
    <div className="admin-overview">
      <div className="admin-stats-grid">
        {stats.map((stat, index) => (
          <div key={index} className="admin-stat-card">
            <div className="admin-stat-number">{stat.number}</div>
            <div className="admin-stat-label">{stat.label}</div>
          </div>
        ))}
      </div>
      <div className="admin-welcome">
        <h2>Welcome to Admin Dashboard</h2>
        <p>
          This is the admin panel for Digital Creators. You can manage services, 
          projects, testimonials, and messages from here. Later, this will be 
          integrated with Supabase for real data management.
        </p>
      </div>
    </div>
  )
}

export default AdminDashboard

