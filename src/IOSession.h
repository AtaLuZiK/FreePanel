#ifndef IOSESSION_H_
#define IOSESSION_H_

/**
 * Provides the base functionality of socket.
 */
class IOSession
{
public:
    IOSession();
    virtual ~IOSession();
    IOSession(const IOSession &) = delete;
    IOSession& operator=(const IOSession &) = delete;

    /**
     * Attaches a socket handle to a IOSession object.
     * @param socket Specifies a handle to a socket.
     * @return Returns true if successful; otherwise false.
     */
    bool Attach(int socket);

    /**
     * Detaches a socket handle from a IOSession object and returns the handle.
     * @return A handle to the socket object.
     */
    int Detach();

    /**
     * Closes this session immediately.
     * This function will try to flush write-buffer
     */
    void Close();

    /**
     * Writes the specified data to remote peer.
     * This operation not sent data to remote peer immediately, but saved in buffer.
     * IOHandler::OnMessageSent will be invoked when the message is actually sent to remote peer.
     * @param data
     * @param sizeInBytes
     */
    void Write(const unsigned char *data, size_t sizeInBytes);

    void WriteString(const char *s);
    void WriteString(const std::string& s);

    virtual bool IsConnected() const final;

    virtual void GetWritten(std::string& buffer) final;
    virtual void EmptyWritten() final;

private:
    int m_Socket;
    bool m_Connected;
    std::string m_Buffer;

};

#endif /* IOSESSION_H_ */
