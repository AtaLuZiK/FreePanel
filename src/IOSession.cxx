#include "IOSession.h"


IOSession::IOSession()
    : m_Socket(0)
    , m_Connected(false)
{
}


IOSession::~IOSession()
{
    if (m_Socket != 0) {
        Close();
    }
}


bool IOSession::Attach(int socket)
{
    if (socket!= 0 && m_Socket == 0) {
        m_Socket = socket;
        m_Connected = true;
        return true;
    }
    return false;
}


int IOSession::Detach()
{
    int socket = m_Socket;
    m_Socket = 0;
    return socket;
}


void IOSession::Close()
{
    close(m_Socket);
    m_Socket = 0;
    m_Connected = false;
}


void IOSession::Write(const unsigned char *data, size_t sizeInBytes)
{
    m_Buffer.append((const char *)data, sizeInBytes);
}


void IOSession::WriteString(const char *s)
{
    m_Buffer.append(s);
}


void IOSession::WriteString(const std::string& s)
{
    m_Buffer.append(s);
}


bool IOSession::IsConnected() const
{
    return m_Connected;
}


void IOSession::GetWritten(std::string& buffer)
{
    buffer = m_Buffer;
}


void IOSession::EmptyWritten()
{
    m_Buffer.clear();
}
