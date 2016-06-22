#ifndef SERVER_H_
#define SERVER_H_
#include "HandlerFactory.h"
#include "FPPacket.h"

class Server
{
public:
    Server();
    virtual ~Server();

    /**
     * A connection has caused a call to accept() on the server port to return. Process the connection appropriately.
     * @param sock The socket connected to the client
     */
    void *HandleConnection(int socket);

    int Run(u_short port);

    /**
     * starts the process of listening for web connections on a specified port. If the port is 0, then dynamically allocate a port and modify the original port variable to reflect the actual port.
     * @param port The port to connect on
     * @return the socket
     */
    int Start(u_short& port);
    void Stop();

protected:
    typedef struct tagConnection
    {
        int clientSocket;
        Server *server;
    } Connection;

    void DispatchRequest(int clientSocket, const char *method, const char *url, const char *queryString);

    /**
     *
     * @param clientSocket
     * @return returns non-zero to disconnect
     */
    int DispatchPacket(int clientSocket);

    int HandleTestCommand(int clientSocket, FPPacket *pPacket);
    int HandleDisconnectCommand(int clientSocket, FPPacket *pPacket);

private:
    static void *StartHandleConnection(void *socket);
    HandlerFactory m_HandlerFactory;
    
};

#endif /* SERVER_H_ */
